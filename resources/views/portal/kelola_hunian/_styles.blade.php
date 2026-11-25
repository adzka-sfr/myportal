<style>
.hn-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:22px}
.hn-stat{background:var(--white);border:1px solid var(--line);border-radius:18px;padding:16px 18px;box-shadow:var(--shadow)}
.hn-stat span{display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:700}
.hn-stat strong{display:block;font-size:1.6rem;margin-top:4px;color:var(--ink)}
.hn-stat small{color:var(--muted)}
.hn-stat.is-alert strong{color:#b4321f}
.hn-meter{height:7px;background:var(--mint);border-radius:99px;margin-top:10px;overflow:hidden}
.hn-meter i{display:block;height:100%;background:var(--green);border-radius:99px}
.hn-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;margin-bottom:20px}
.hn-filters{display:flex;flex-wrap:wrap;gap:8px}
.hn-filter{border:1px solid var(--line);background:var(--white);color:var(--ink-2);border-radius:99px;padding:7px 14px;font:inherit;font-size:.82rem;font-weight:600;cursor:pointer}
.hn-filter.is-active{background:var(--ink);border-color:var(--ink);color:#fff}
.hn-floor{margin-bottom:30px}
.hn-floor-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;padding-bottom:10px;border-bottom:2px dashed var(--line)}
.hn-floor-head h2{margin:0;font-size:1.15rem}
.hn-floor-head small{color:var(--muted);margin-left:8px;font-weight:500}
.hn-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px}
.hn-room{position:relative;display:flex;flex-direction:column;gap:10px;background:var(--white);border:2px solid var(--line);border-top-width:6px;border-radius:16px;padding:16px;box-shadow:var(--shadow)}
.hn-room.st-available{border-color:#b9d8c3;border-top-color:#43825e}
.hn-room.st-occupied{border-color:#e3c3ac;border-top-color:#a65a3a}
.hn-room.st-reserved{border-color:#c6d3e4;border-top-color:#5076a1}
.hn-room.st-maintenance{border-color:#e5d9a8;border-top-color:#b8921a;background:repeating-linear-gradient(135deg,var(--white),var(--white) 10px,#fbf6e4 10px,#fbf6e4 20px)}
.hn-room.pay-overdue{box-shadow:0 0 0 3px rgba(180,50,31,.25),var(--shadow)}
.hn-room[hidden]{display:none}
.hn-room-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.hn-number{font-size:1.7rem;font-weight:800;line-height:1;color:var(--ink)}
.hn-type{display:block;font-size:.78rem;color:var(--muted);margin-top:4px}
.hn-badge{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:4px 9px;border-radius:99px;white-space:nowrap}
.st-available .hn-badge{background:#e3f1e9;color:#2f6a49}
.st-occupied .hn-badge{background:#f3e4d3;color:#814329}
.st-reserved .hn-badge{background:#e2eaf4;color:#3d5f87}
.st-maintenance .hn-badge{background:#f6eecb;color:#7d6410}
.hn-occupant{display:flex;flex-direction:column;gap:2px;font-size:.88rem;min-height:44px}
.hn-occupant strong{font-size:.95rem}
.hn-occupant small,.hn-empty-text{color:var(--muted);font-size:.8rem}
.hn-pay{display:inline-block;font-size:.76rem;font-weight:700;border-radius:8px;padding:4px 9px;align-self:flex-start}
.hn-pay.ok{background:#e3f1e9;color:#2f6a49}
.hn-pay.soon{background:#f6eecb;color:#7d6410}
.hn-pay.overdue{background:#f9dcd6;color:#b4321f}
.hn-tags{display:flex;flex-wrap:wrap;gap:5px}
.hn-tags span{font-size:.7rem;background:var(--mint);color:var(--ink-2);border-radius:6px;padding:3px 7px}
.hn-price{font-weight:700;color:var(--ink);font-size:.92rem}
.hn-price small{font-weight:500;color:var(--muted)}
.hn-note{font-size:.78rem;color:var(--ink-2);background:var(--paper);border-radius:8px;padding:6px 8px}
.hn-actions{display:flex;gap:6px;align-items:center;margin-top:auto;padding-top:10px;border-top:1px solid var(--line);flex-wrap:wrap}
.hn-actions form{margin:0}
.hn-add-room{display:flex;align-items:center;justify-content:center;min-height:120px;border:2px dashed var(--line);border-radius:16px;background:transparent;color:var(--muted);font:inherit;font-weight:700;cursor:pointer}
.hn-add-room:hover{border-color:var(--green);color:var(--green-dark)}
.hn-add-room[hidden]{display:none}
.hn-dialog{border:0;border-radius:20px;padding:0;width:min(640px,calc(100vw - 28px));max-height:calc(100vh - 40px);box-shadow:0 30px 80px rgba(0,0,0,.3);background:var(--white);color:var(--ink)}
.hn-dialog::backdrop{background:rgba(58,11,8,.55)}
.hn-dialog form{padding:24px;display:flex;flex-direction:column;gap:16px}
.hn-dialog h2{margin:0}
.hn-dialog [hidden]{display:none !important}
.hn-dialog .form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.hn-dialog .span-2{grid-column:1/-1}
.hn-dialog label{display:flex;flex-direction:column;gap:6px;font-size:.85rem;font-weight:600}
.hn-dialog input,.hn-dialog select,.hn-dialog textarea{font:inherit;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink)}
.hn-dialog fieldset{border:1px dashed var(--line);border-radius:14px;padding:14px;margin:0}
.hn-dialog legend{font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--green);font-weight:800;padding:0 6px}
.hn-dialog .hn-dialog-actions{display:flex;justify-content:flex-end;gap:10px}
.hn-dialog .hn-errors{margin:0;padding:10px 14px 10px 28px;background:#f9dcd6;color:#b4321f;border-radius:10px;font-size:.84rem}
.hn-dialog .hn-checks{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px}
.hn-dialog .hn-checks label{flex-direction:row;align-items:center;gap:8px;font-weight:500;border:1px solid var(--line);border-radius:10px;padding:8px 10px;cursor:pointer}
.hn-dialog .hn-checks input{padding:0;width:auto}
.hn-dialog .hn-hint{font-size:.78rem;color:var(--muted);font-weight:400}
.hn-tabs{display:flex;gap:6px;margin-bottom:22px;border-bottom:1px solid var(--line);overflow-x:auto;white-space:nowrap}
.hn-chat{display:flex;flex-direction:column;gap:12px;max-height:60vh;overflow-y:auto;padding:4px 2px}
.hn-msg{max-width:78%;padding:10px 14px;border-radius:14px;background:var(--mint);align-self:flex-start;font-size:.9rem}
.hn-msg.is-mine{align-self:flex-end;background:#f3e4d3}
.hn-msg small{display:block;color:var(--muted);font-size:.72rem;margin-bottom:4px}
.hn-msg p{margin:0;white-space:pre-wrap;word-break:break-word}
.hn-msg img{display:block;max-width:100%;max-height:260px;border-radius:10px;margin-top:8px;cursor:zoom-in}
.hn-reply{display:flex;flex-direction:column;gap:10px;margin-top:16px;border-top:1px solid var(--line);padding-top:16px}
.hn-reply textarea,.hn-reply input[type=text],.hn-reply select,.hn-field input,.hn-field textarea,.hn-field select{font:inherit;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink);width:100%}
.hn-field{display:flex;flex-direction:column;gap:6px;font-size:.85rem;font-weight:600}
.hn-status{display:inline-block;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:3px 9px;border-radius:99px;background:var(--mint);color:var(--ink-2)}
.hn-status.s-open{background:#f9dcd6;color:#b4321f}
.hn-status.s-in_progress{background:#f6eecb;color:#7d6410}
.hn-status.s-resolved{background:#e3f1e9;color:#2f6a49}
.hn-dot{display:inline-block;width:8px;height:8px;border-radius:99px;background:#b4321f;margin-right:6px}
.hn-kv{display:grid;grid-template-columns:140px 1fr;gap:8px 14px;font-size:.9rem;margin:0}
.hn-kv dt{color:var(--muted)}
.hn-kv dd{margin:0;font-weight:600}
.hn-alert{padding:14px 18px;border-radius:14px;margin-bottom:18px;font-weight:600}
.hn-alert.overdue{background:#f9dcd6;color:#b4321f}
.hn-alert.soon{background:#f6eecb;color:#7d6410}
.hn-big{font-size:1.9rem;font-weight:800;color:var(--ink);line-height:1.1}
.hn-tabs a{padding:10px 16px;font-weight:700;font-size:.9rem;color:var(--muted);text-decoration:none;border-bottom:3px solid transparent;margin-bottom:-1px}
.hn-tabs a.is-active{color:var(--ink);border-bottom-color:var(--green)}
.hn-split{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:20px;align-items:start}
.hn-panel{background:var(--white);border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:var(--shadow)}
.hn-panel h2{margin:0 0 4px;font-size:1.05rem}
.hn-panel>p{margin:0 0 14px;color:var(--muted);font-size:.84rem}
.hn-table{width:100%;border-collapse:collapse;font-size:.86rem}
.hn-table th{text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:8px 10px;border-bottom:1px solid var(--line)}
.hn-table td{padding:10px;border-bottom:1px solid var(--line);vertical-align:top}
.hn-table .align-right{text-align:right;white-space:nowrap}
.hn-fac-list{list-style:none;margin:0 0 14px;padding:0;display:flex;flex-direction:column;gap:8px}
.hn-fac-list li{display:flex;gap:6px;align-items:center}
.hn-fac-list form{margin:0;display:flex;gap:6px;flex:1}
.hn-fac-list input{flex:1;min-width:0;font:inherit;padding:7px 10px;border:1px solid var(--line);border-radius:9px;background:#fff}
.hn-fac-list small{color:var(--muted);white-space:nowrap}
.hn-feed{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:12px}
.hn-feed li{font-size:.84rem;border-left:3px solid var(--line);padding-left:10px}
.hn-feed small{display:block;color:var(--muted);font-size:.74rem}
.hn-act{display:inline-block;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:3px 8px;border-radius:99px;background:var(--mint);color:var(--ink-2);white-space:nowrap}
.hn-act.act-check_in,.hn-act.act-payment,.hn-act.act-payment_matched,.hn-act.act-maintenance_finished{background:#e3f1e9;color:#2f6a49}
.hn-act.act-check_out,.hn-act.act-reservation_cancelled,.hn-act.act-room_deleted,.hn-act.act-facility_deleted,.hn-act.act-proof_deleted{background:#f9dcd6;color:#b4321f}
.hn-act.act-reserved{background:#e2eaf4;color:#3d5f87}
.hn-act.act-maintenance_started{background:#f6eecb;color:#7d6410}
.hn-logfilter{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:16px}
.hn-logfilter label{display:flex;flex-direction:column;gap:4px;font-size:.78rem;font-weight:700;color:var(--muted)}
.hn-logfilter select{font:inherit;padding:8px 10px;border:1px solid var(--line);border-radius:10px;background:#fff;min-width:160px}
@media(max-width:900px){.hn-split{grid-template-columns:1fr}}
.hn-tab-count{display:inline-block;min-width:20px;margin-left:6px;padding:1px 7px;border-radius:99px;background:#b4321f;color:#fff;font-size:.7rem;text-align:center}
.hn-period{grid-column:1/-1;border:1px dashed var(--line);border-radius:14px;padding:14px;margin:0}
.hn-period[hidden]{display:none}
.hn-period .form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.hn-period .span-2{grid-column:1/-1}
.hn-preview{margin:10px 0 0;font-size:.85rem;font-weight:700;color:var(--green-dark)}
.hn-thumb{width:84px;height:84px;object-fit:cover;border-radius:10px;border:1px solid var(--line);cursor:zoom-in;background:var(--mint);display:block}
.hn-proof-img{max-width:100%;max-height:75vh;display:block;margin:0 auto;border-radius:10px}
.hn-match-form{display:flex;gap:6px;align-items:center;margin:0}
.hn-match-form input{width:130px;font:inherit;padding:6px 9px;border:1px solid var(--line);border-radius:9px;background:#fff}
@media(max-width:560px){.hn-dialog .form-grid{grid-template-columns:1fr}}
</style>
