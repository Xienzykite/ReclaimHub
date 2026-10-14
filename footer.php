</main>
<p style="text-align:center;color:var(--mut);font-size:.85rem;padding:0 1rem 2rem">PIT Finds · campus lost and found · <a href="admin/login.php" style="color:inherit">Staff portal</a></p>
<dialog id="dlg"><form method="post" action="claim.php"><?= csrf() ?><input type="hidden" name="item_id" id="cid"><h2 id="ct">Claim item</h2>
<label for="m">Proof or details only the owner would know</label><textarea id="m" name="message" required></textarea>
<p style="color:var(--mut);font-size:.88rem">If approved, collect it at <?= e(PICKUP_POINT) ?> with your school ID.</p>
<div class="row" style="margin-top:1rem"><button class="btn a">Send claim</button><button type="button" class="btn g" onclick="dlg.close()">Cancel</button></div></form></dialog>
<?php if ($toast) echo '<div class="toast" id="toast">' . e($toast) . '</div>'; ?>
<script>
document.querySelectorAll('[data-n]').forEach(el=>{const n=+el.dataset.n,t0=performance.now();(function f(t){const p=Math.min(1,(t-t0)/900);el.textContent=Math.round(n*(1-Math.pow(1-p,3)));if(p<1)requestAnimationFrame(f)})(t0)});
document.querySelectorAll('[data-claim]').forEach(b=>b.onclick=()=>{cid.value=b.dataset.claim;ct.textContent='Claim: '+b.dataset.title;dlg.showModal()});
const t=document.getElementById('toast');if(t)setTimeout(()=>t.remove(),3000);
</script></body></html>
