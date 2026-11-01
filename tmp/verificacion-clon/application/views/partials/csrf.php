<script>
(function(){
const name=<?= json_encode($this->security->get_csrf_token_name()); ?>,token=<?= json_encode($this->security->get_csrf_hash()); ?>;
function protect(body){if(body instanceof FormData||body instanceof URLSearchParams){body.set(name,token);return body;}if(typeof body==='string'){const data=new URLSearchParams(body);data.set(name,token);return data.toString();}return new URLSearchParams({[name]:token});}
const original=window.fetch;
window.fetch=function(input,options){const url=new URL(typeof input==='string'?input:input.url,location.href);const opts=Object.assign({},options||{});if(url.origin===location.origin&&String(opts.method||'GET').toUpperCase()==='POST')opts.body=protect(opts.body);return original.call(this,input,opts);};
const open=XMLHttpRequest.prototype.open,send=XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.open=function(method,url){this.tiendaPost=String(method).toUpperCase()==='POST'&&new URL(url,location.href).origin===location.origin;return open.apply(this,arguments);};
XMLHttpRequest.prototype.send=function(body){return send.call(this,this.tiendaPost?protect(body):body);};
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('form').forEach(function(form){if(String(form.method).toLowerCase()!=='post')return;const field=document.createElement('input');field.type='hidden';field.name=name;field.value=token;form.appendChild(field);});});
})();
</script>
