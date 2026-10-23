import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { initializeAdminSidebar, sidebarInitiallyOpen } from '../resources/js/admin-sidebar.js';

class Element extends EventTarget {
    constructor(doc, name) { super(); this.doc=doc; this.name=name; this.dataset={}; this.attributes={}; this.inert=false; this.hidden=false; }
    setAttribute(key,value) { this.attributes[key]=value; }
    focus() { if (this.name==='toggle' && this.doc.main.inert) return; this.doc.activeElement=this; }
    getClientRects() { return [{}]; }
    click() { this.dispatchEvent(new Event('click')); }
}

function fixture({mobile=false, saved=null, denied=false}={}) {
    const doc = new EventTarget();
    doc.documentElement={dataset:{}};
    const classes=new Set();
    doc.body={classList:{toggle:(name,on)=>on?classes.add(name):classes.delete(name)}};
    const sidebar=new Element(doc,'sidebar'),toggle=new Element(doc,'toggle'),main=new Element(doc,'main');
    const close=new Element(doc,'close'),backdrop=new Element(doc,'backdrop'),link=new Element(doc,'link');
    doc.main=main; doc.activeElement=main;
    sidebar.contains=element=>[sidebar,close,link].includes(element);
    sidebar.querySelector=()=>close;
    sidebar.querySelectorAll=()=>[close,link];
    doc.getElementById=()=>sidebar;
    doc.querySelector=selector=>({'[data-sidebar-toggle]':toggle,'.admin-main':main,'.admin-sidebar-backdrop':backdrop})[selector];
    doc.querySelectorAll=()=>[close,backdrop];
    const media=new EventTarget();media.matches=mobile;
    const storage=new Map(saved?[['mediway_sidebar_state',saved]]:[]);
    const win=Object.assign(new EventTarget(),{matchMedia:()=>media,localStorage:{getItem:key=>{if(denied)throw Error('blocked');return storage.get(key)??null;},setItem:(key,value)=>{if(denied)throw Error('blocked');storage.set(key,value);}}});
    const key=(name,shiftKey=false)=>{const event=new Event('keydown',{cancelable:true});event.key=name;event.shiftKey=shiftKey;doc.dispatchEvent(event);return event;};
    const resize=value=>{media.matches=value;media.dispatchEvent(new Event('change'));};
    initializeAdminSidebar(doc,win);
    return {doc,win,sidebar,toggle,main,close,backdrop,link,classes,storage,key,resize};
}

test('desktop defaults open and remembers collapse across pages',()=>{
    const f=fixture();
    assert.equal(f.toggle.attributes['aria-expanded'],'true');
    f.toggle.click();
    assert.equal(f.sidebar.inert,true);
    assert.equal(f.toggle.attributes['aria-label'],'Mở thanh điều hướng');
    assert.equal(f.classes.has('admin-sidebar-collapsed'),true);
    assert.equal(f.storage.get('mediway_sidebar_state'),'closed');
    assert.equal(sidebarInitiallyOpen(false,'closed'),false);
    f.toggle.click();assert.equal(f.sidebar.inert,false);
});

test('mobile drawer restores focus, keeps the page inert only while open and does not overwrite desktop preference',()=>{
    const f=fixture({mobile:true,saved:'open'});
    assert.equal(f.sidebar.inert,true);
    f.toggle.click();
    assert.equal(f.main.inert,true);
    assert.equal(f.backdrop.hidden,false);
    assert.equal(f.doc.activeElement,f.close);
    f.backdrop.click();
    assert.equal(f.main.inert,false);
    assert.equal(f.doc.activeElement,f.toggle);
    assert.equal(f.storage.get('mediway_sidebar_state'),'open');
    f.toggle.click();f.key('Escape');
    assert.equal(f.sidebar.inert,true);
    assert.equal(f.doc.activeElement,f.toggle);
});

test('Tab and Shift+Tab stay in the mobile drawer',()=>{
    const f=fixture({mobile:true});f.toggle.click();
    f.link.focus();assert.equal(f.key('Tab').defaultPrevented,true);
    assert.equal(f.doc.activeElement,f.close);
    assert.equal(f.key('Tab',true).defaultPrevented,true);
    assert.equal(f.doc.activeElement,f.link);
});

test('resizing restores desktop preference and releases the background',()=>{
    const f=fixture({saved:'closed'});f.resize(true);f.toggle.click();
    f.resize(false);
    assert.equal(f.sidebar.inert,true);assert.equal(f.main.inert,false);assert.equal(f.backdrop.hidden,true);
});

test('blocked storage does not break navigation and initialization is idempotent',()=>{
    const f=fixture({denied:true});initializeAdminSidebar(f.doc,f.win);f.toggle.click();
    assert.equal(f.sidebar.inert,true);f.toggle.click();assert.equal(f.sidebar.inert,false);
});

test('saved state is applied in the head before styles, modules or body can render',()=>{
    const layout=readFileSync(new URL('../resources/views/admin/layouts/app.blade.php',import.meta.url),'utf8');
    const script=layout.match(/<script data-sidebar-prepaint>([\s\S]*?)<\/script>/);
    assert.ok(script);
    assert.ok(script.index<layout.indexOf('@vite'));
    assert.ok(script.index<layout.indexOf('<body'));
    for(const [mobile,saved,denied,expected] of [
        [false,'closed',false,'closed'],[false,'open',false,'open'],
        [false,null,false,'open'],[true,'open',false,'closed'],
        [false,null,true,'open'],[true,null,true,'closed'],
    ]) {
        const doc={documentElement:{dataset:{}}};
        runInNewContext(script[1],{document:doc,window:{matchMedia:()=>({matches:mobile})},
            localStorage:{getItem:()=>{if(denied)throw Error('blocked');return saved;}}});
        assert.equal(doc.documentElement.dataset.sidebarState,expected);
    }
});

test('initialization and history restore are immediate; only user toggles animate',()=>{
    const f=fixture({saved:'closed'});
    assert.equal(f.doc.documentElement.dataset.sidebarState,'closed');
    assert.equal(f.doc.documentElement.dataset.sidebarAnimated,'false');
    f.toggle.click();
    assert.equal(f.doc.documentElement.dataset.sidebarState,'open');
    assert.equal(f.doc.documentElement.dataset.sidebarAnimated,'true');
    f.win.dispatchEvent(new Event('pagehide'));
    assert.equal(f.doc.documentElement.dataset.sidebarAnimated,'false');
    // Another page changed the preference while this page was in the back/forward cache.
    f.storage.set('mediway_sidebar_state','closed');
    f.win.dispatchEvent(new Event('pageshow'));
    assert.equal(f.doc.documentElement.dataset.sidebarState,'closed');
    assert.equal(f.doc.documentElement.dataset.sidebarAnimated,'false');
    assert.equal(f.sidebar.inert,true);
    assert.equal(f.toggle.attributes['aria-expanded'],'false');
    assert.equal(f.main.inert,false);
});

test('returning to a mobile page closes the drawer without changing the desktop preference',()=>{
    const f=fixture({mobile:true,saved:'open'});f.toggle.click();
    f.win.dispatchEvent(new Event('pageshow'));
    assert.equal(f.sidebar.inert,true);
    assert.equal(f.main.inert,false);
    assert.equal(f.backdrop.hidden,true);
    assert.equal(f.storage.get('mediway_sidebar_state'),'open');
    assert.equal(f.doc.documentElement.dataset.sidebarAnimated,'false');
});
