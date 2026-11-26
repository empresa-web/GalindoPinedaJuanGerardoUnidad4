'use strict';
for(const a of document.querySelectorAll('[data-campaign]')){
 a.addEventListener('click',()=>{const s=document.getElementById('campaign');if(s)s.value=a.dataset.campaign;});
}
const mxn=document.getElementById('mxn');
if(mxn){
 const usd=document.getElementById('usd'), status=document.getElementById('fx-status');
 let rate=null;
 function update(){usd.textContent=rate ? (Number(mxn.value)*rate).toLocaleString('en-US',{style:'currency',currency:'USD'}) : 'No disponible';}
 mxn.addEventListener('input',update);
 fetch('https://api.frankfurter.dev/v2/rate/mxn/usd',{headers:{Accept:'application/json'}})
  .then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
  .then(data=>{if(!(data.rate>0))throw new Error('Tasa inválida');rate=data.rate;status.textContent=`Frankfurter API · MXN/USD: ${rate} · fecha de referencia: ${data.date}`;update();})
  .catch(()=>{status.textContent='Servicio externo no disponible. Comprueba tu conexión a Internet.';update();});
}
