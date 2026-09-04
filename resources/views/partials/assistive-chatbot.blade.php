{{-- Core LMS floating FAQ assistant --}}
<div id="uwezo-assistive-chat" style="position:fixed;right:16px;bottom:16px;z-index:1040;">
  <div id="uwezo-assistive-chat-panel" hidden style="width:min(340px,calc(100vw - 24px));background:#fff;border:1px solid #ccc;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.15);margin-bottom:8px;display:flex;flex-direction:column;max-height:70vh;">
    <div style="padding:8px 12px;background:#f0f7ff;display:flex;justify-content:space-between;"><strong>LMS help</strong><button type="button" id="uwezo-chat-close" class="btn btn-xs btn-link">Close</button></div>
    <div id="uwezo-chat-log" style="padding:8px;overflow:auto;flex:1;min-height:140px;font-size:13px;"></div>
    <form id="uwezo-chat-form" style="display:flex;gap:4px;padding:8px;border-top:1px solid #eee;">
      <input id="uwezo-chat-input" class="form-control form-control-sm" placeholder="Ask about workflow, SMS, risk…">
      <button class="btn btn-sm btn-primary" type="submit">Send</button>
    </form>
  </div>
  <button type="button" id="uwezo-chat-toggle" class="btn btn-info shadow">Help</button>
</div>
<script>
(function(){
  var faq=[{k:['sms','whatsapp','alert'],a:'Milestone SMS uses EBIS (EBISCLOUD_SMS_*). WhatsApp is optional via WHATSAPP_ENABLED + Meta credentials.'},{k:['recommend','amount','decline'],a:'LoanRecommendationService suggests amounts; PathwayPrescriptionService adds next steps. Officers confirm — no silent auto-reject.'},{k:['phase','aging','triage'],a:'Phase-aging analytics suggests review order by SLA warn (≥7d) / breach (≥14d).'},{k:['status','workflow'],a:'Portal → CO → HQ → Accounts → Secretariat → training → disbursement. Drupal sync triggers member status SMS.'},{k:['applicant','chat','login','loans'],a:'Applicants sign in via Help chat on loans.uwezo.go.ke / loans-staging (Drupal email/password), then Apply or Check status (APP-…).'}];
  function ans(q){q=(q||'').toLowerCase();var b=null,s=0;faq.forEach(function(e){var n=e.k.reduce(function(a,k){return a+(q.indexOf(k)>=0?1:0);},0);if(n>s){s=n;b=e;}});return b&&s?b.a:'Rule-based LMS helper. Try SMS, recommendations, phase aging, workflow, or applicant loans chat.';}
  var panel=document.getElementById('uwezo-assistive-chat-panel'),log=document.getElementById('uwezo-chat-log'),tog=document.getElementById('uwezo-chat-toggle');
  function add(t){var d=document.createElement('div');d.style.marginBottom='6px';d.textContent=t;log.appendChild(d);log.scrollTop=log.scrollHeight;}
  add('Assistive FAQ for Uwezo LMS staff.');
  function open(o){panel.hidden=!o;panel.style.display=o?'flex':'none';}
  open(false);
  tog.onclick=function(){open(panel.hidden);};
  document.getElementById('uwezo-chat-close').onclick=function(){open(false);};
  document.getElementById('uwezo-chat-form').onsubmit=function(e){e.preventDefault();var v=document.getElementById('uwezo-chat-input').value.trim();if(!v)return;add('You: '+v);document.getElementById('uwezo-chat-input').value='';add(ans(v));};
})();
</script>
