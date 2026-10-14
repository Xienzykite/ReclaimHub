</main>
<?php if ($toast) echo '<div class="toast" id="toast">' . e($toast) . '</div>'; ?>
<script>
document.querySelectorAll('[data-n]').forEach(el=>{const n=+el.dataset.n,t0=performance.now();(function f(t){const p=Math.min(1,(t-t0)/900);el.textContent=Math.round(n*(1-Math.pow(1-p,3)));if(p<1)requestAnimationFrame(f)})(t0)});
const t=document.getElementById('toast');if(t)setTimeout(()=>t.remove(),3000);
</script></body></html>
