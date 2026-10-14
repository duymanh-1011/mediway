<style>
.pf-page{max-width:1020px;margin:0 auto}
.pf-heading{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:22px}
.pf-heading .page-description{margin:7px 0 0;color:#64748b}
.pf-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:27px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.pf-section{border-bottom:1px solid #edf2f7;padding-bottom:18px;margin-bottom:23px}
.pf-section h2{font-size:17px;margin:0 0 8px;color:#0f172a}
.pf-section p{margin:0;font-size:13px;color:#64748b;line-height:1.6}
.pf-section-gap{margin-top:31px}
.pf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.pf-field{min-width:0}
.pf-full{grid-column:1/-1}
.pf-field label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:8px}
.pf-field label span{color:#dc2626}
.pf-field input,.pf-field select,.pf-field textarea{display:block;width:100%;border:1px solid #cbd5e1;border-radius:9px;padding:11px 13px;outline:none;background:#fff;color:#0f172a;font-family:inherit;font-size:13px}
.pf-field input,.pf-field select{min-height:43px}
.pf-field textarea{resize:vertical;min-height:105px}
.pf-field input:disabled{background:#f1f5f9;color:#64748b}
.pf-field input:focus,.pf-field select:focus,.pf-field textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.pf-file-input{cursor:pointer;background:#f8fafc!important;padding:8px!important}
.pf-file-input::file-selector-button{border:0;background:#dbeafe;color:#1d4ed8;border-radius:7px;padding:8px 12px;font-weight:700;margin-right:12px;cursor:pointer}
.pf-help{display:block;font-size:12px;color:#64748b;line-height:1.6;margin-top:7px}
.pf-help a{color:#2563eb;font-weight:700;text-decoration:none}
.pf-help a:hover{text-decoration:underline}
.pf-error{display:block;color:#dc2626;font-size:12px;margin-top:6px}
.pf-alert{padding:14px 16px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:9px;margin-bottom:22px;font-size:13px}
.pf-alert ul{margin:8px 0 0;padding-left:20px}
.pf-note{margin-top:26px;padding:15px;border-radius:9px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-size:13px;line-height:1.7}
.pf-actions{display:flex;justify-content:flex-end;gap:12px;border-top:1px solid #edf2f7;margin-top:25px;padding-top:23px}
.pf-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 16px;border:1px solid transparent;border-radius:9px;text-decoration:none;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer}
.pf-primary{background:#2563eb;color:#fff}
.pf-primary:hover{background:#1d4ed8;color:#fff}
.pf-light{background:#fff;color:#334155;border-color:#cbd5e1}
@media(max-width:650px){
    .pf-card{padding:17px}
    .pf-grid{grid-template-columns:1fr}
    .pf-full{grid-column:auto}
    .pf-file-input::file-selector-button{margin-bottom:5px}
}
</style>