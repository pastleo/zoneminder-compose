var z=new Map;function P(q){let{id:j,title:x,content:E,actions:A=[],onClose:J,className:K=""}=q,h=z.get(j);if(!h)h=document.createElement("dialog"),h.id=j,h.className=`modal ${K}`,document.body.appendChild(h),z.set(j,h),h.addEventListener("click",(w)=>{if(w.target===h)h.close()}),h.addEventListener("close",()=>{J?.()});let L=A.length?`<div class="modal-action">
        ${A.map((w,B)=>`<button type="${w.type||"button"}" class="${w.className||"btn"}" data-action-index="${B}">${w.label}</button>`).join("")}
      </div>`:"";return h.innerHTML=`
    <div class="modal-box">
      ${x?`<h3 class="font-bold text-lg">${x}</h3>`:""}
      <div class="modal-content py-4">${E}</div>
      ${L}
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  `,A.forEach((w,B)=>{let F=h.querySelector(`[data-action-index="${B}"]`);if(F)F.addEventListener("click",(O)=>{if(w.onClick?.(O,h),w.closeOnClick!==!1)h.close()})}),h}function R(q){let j=z.get(q)||document.getElementById(q);if(j)j.showModal()}function S(q){let j=z.get(q)||document.getElementById(q);if(j)j.close()}function T(q,j="Confirm"){return new Promise((x)=>{P({id:"confirm-dialog",title:j,content:`<p>${q}</p>`,actions:[{label:"Cancel",className:"btn btn-ghost",onClick:()=>x(!1)},{label:"OK",className:"btn btn-primary",onClick:()=>x(!0)}],onClose:()=>x(!1)}).showModal()})}
export{P as b,R as c,S as d,T as e};
