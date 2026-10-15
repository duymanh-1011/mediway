
<style>
.dr-page{max-width:1120px;margin:0 auto}
.dr-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:22px}
.dr-description{font-size:13px;color:#64748b;margin:7px 0 0}
.dr-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:26px;margin-bottom:20px;box-shadow:0 3px 15px rgba(15,23,42,.035)}
.dr-section{padding-bottom:16px;border-bottom:1px solid #e2e8f0;margin-bottom:20px}
.dr-section h2{font-size:17px;margin:0 0 7px;color:#0f172a}
.dr-section p{font-size:13px;color:#64748b;line-height:1.6;margin:0}
.dr-gap{margin-top:32px}
.dr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.dr-full{grid-column:1/-1}
.dr-field{min-width:0}
.dr-field label,.dr-document-card>label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:8px}
.dr-field label span{color:#dc2626}
.dr-field input,.dr-field select,.dr-field textarea{box-sizing:border-box;width:100%;min-height:42px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#0f172a;padding:10px 12px;font-family:inherit;font-size:13px;outline:none}
.dr-field input:focus,.dr-field select:focus,.dr-field textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.dr-field input:disabled{background:#f1f5f9;color:#64748b}
.dr-field textarea{resize:vertical;line-height:1.6}
.dr-help{display:block;font-size:12px;color:#64748b;margin-top:7px;line-height:1.6}
.dr-error{display:block;font-size:12px;color:#dc2626;margin-top:6px}
.dr-documents{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.dr-document-card{min-width:0;border:1px solid #e2e8f0;background:#fafcff;border-radius:12px;padding:16px}
.dr-document-preview{height:190px;border:1px dashed #cbd5e1;border-radius:9px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;color:#94a3b8;font-size:12px;overflow:hidden;margin-bottom:12px}
.dr-document-preview img{width:100%;height:100%;object-fit:contain}
.dr-file-symbol{background:#dbeafe;color:#1d4ed8;padding:12px;border-radius:10px;font-size:18px;font-weight:800}
.dr-file-link{display:inline-block;color:#2563eb;font-size:12px;font-weight:700;margin-bottom:11px;text-decoration:none}
.dr-upload{display:block;width:100%;font-size:12px;max-width:100%}
.dr-btn{display:inline-flex;justify-content:center;align-items:center;min-height:41px;padding:10px 16px;border:1px solid transparent;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;font-family:inherit;cursor:pointer}
.dr-primary{color:#fff;background:#2563eb}
.dr-primary:hover{background:#1d4ed8;color:#fff}
.dr-light{color:#334155;background:#fff;border-color:#cbd5e1}
.dr-actions{display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;border-top:1px solid #e2e8f0;padding-top:22px;margin-top:28px}
.dr-alert{padding:14px 16px;border-radius:9px;margin-bottom:20px;font-size:13px}
.dr-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.dr-alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.dr-alert ul{padding-left:20px;margin:7px 0 0}
.dr-info{padding:14px 16px;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;border-radius:9px;font-size:13px;line-height:1.7;margin-top:24px}
@media(max-width:700px){
.dr-card{padding:16px}
.dr-grid,.dr-documents{grid-template-columns:1fr}
.dr-full{grid-column:auto}
}
</style>
