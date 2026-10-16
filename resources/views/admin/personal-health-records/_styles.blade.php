<style>
.hr-page{max-width:1040px;margin:0 auto}
.hr-heading{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;margin-bottom:22px}
.hr-heading .page-description{margin:7px 0 0;color:#64748b}
.hr-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:27px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.hr-section{border-bottom:1px solid #edf2f7;padding-bottom:18px;margin-bottom:23px}
.hr-section h2{font-size:17px;color:#0f172a;margin:0 0 8px}
.hr-section p{font-size:13px;color:#64748b;margin:0;line-height:1.6}
.hr-section-gap{margin-top:30px}
.hr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.hr-field{min-width:0}
.hr-full{grid-column:1/-1}
.hr-field label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:8px}
.hr-field label span{color:#dc2626}
.hr-field input,.hr-field select,.hr-field textarea{width:100%;min-height:43px;background:#fff;border:1px solid #cbd5e1;border-radius:9px;padding:11px 13px;color:#0f172a;outline:none;font-family:inherit;font-size:13px}
.hr-field textarea{resize:vertical}
.hr-field input:focus,.hr-field select:focus,.hr-field textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.hr-field input:disabled,.hr-field input:read-only{background:#f1f5f9;color:#64748b}
.hr-help{display:block;margin-top:7px;color:#64748b;font-size:12px;line-height:1.6}
.hr-error{display:block;margin-top:6px;color:#dc2626;font-size:12px}
.hr-alert{border:1px solid #fecaca;background:#fef2f2;color:#991b1b;padding:14px 16px;border-radius:9px;margin-bottom:21px;font-size:13px}
.hr-alert ul{margin:8px 0 0;padding-left:19px}
.hr-note{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:9px;padding:15px;margin-top:26px;font-size:13px;line-height:1.7}
.hr-actions{display:flex;justify-content:flex-end;gap:12px;border-top:1px solid #edf2f7;margin-top:25px;padding-top:23px}
.hr-btn{display:inline-flex;justify-content:center;align-items:center;min-height:41px;padding:10px 16px;border:1px solid transparent;border-radius:9px;text-decoration:none;font-family:inherit;font-weight:700;font-size:13px;cursor:pointer}
.hr-primary{background:#2563eb;color:#fff}
.hr-primary:hover{background:#1d4ed8;color:#fff}
.hr-light{background:#fff;border-color:#cbd5e1;color:#334155}
@media(max-width:650px){.hr-card{padding:17px}.hr-grid{grid-template-columns:1fr}.hr-full{grid-column:auto}}
</style>