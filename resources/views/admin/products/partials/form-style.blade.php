<style>
    .form-container { max-width: 920px; margin: 0 auto; padding: 20px; }
    .form-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 30px; color: #cbd5e1; }
    .form-header { margin-bottom: 24px; border-bottom: 1px solid #1e293b; padding-bottom: 18px; }
    .form-header h1 { font-size: 1.6rem; color: #fff; margin: 0; }
    .form-header p { color: #94a3b8; font-size: .9rem; margin-top: 5px; }
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: .78rem; color: #94a3b8; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 14px; background: #020617; border: 1px solid #334155; border-radius: 8px; color: #fff; }
    .row-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .row-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .row-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .checkbox-box { display: flex; align-items: center; gap: 10px; background: #020617; padding: 12px 14px; border-radius: 8px; border: 1px solid #334155; color: #cbd5e1; font-size: .88rem; }
    .checkbox-box input[type="checkbox"] { width: 18px !important; height: 18px !important; margin: 0; accent-color: #ea580c; }
    .error-box { background: #4c0519; border: 1px solid #9f1239; color: #fda4af; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: .85rem; }
    .error-box ul { margin: 10px 0 0 20px; }
    .form-actions { display: flex; gap: 14px; margin-top: 24px; border-top: 1px solid #1e293b; padding-top: 18px; }
    .btn-cancel, .btn-save { padding: 12px 16px; border-radius: 8px; font-weight: 800; text-align: center; }
    .btn-cancel { width: 30%; border: 1px solid #334155; color: #cbd5e1; text-decoration: none; }
    .btn-save { width: 70%; border: 0; background: #ea580c; color: #fff; cursor: pointer; }
    @media (max-width: 780px) {
        .row-2, .row-3, .row-4 { grid-template-columns: 1fr; }
        .form-actions { flex-direction: column; }
        .btn-cancel, .btn-save { width: 100%; }
    }
</style>
