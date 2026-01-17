import{e as l}from"/skins/modern/dist/main-xynt3e4r.js";var d=12,m=400;class x{config;events=[];selectedIds=new Set;currentPage=1;totalEvents=0;isLoading=!1;container=null;gridEl=null;loadingEl=null;emptyEl=null;constructor(e){this.config={limit:d,thumbnailWidth:m,showMonitorName:!1,showCheckboxes:!1,showPagination:!1,...e},this.initElements(),this.initEventListeners()}initElements(){let{containerId:e}=this.config;if(this.container=document.getElementById(e),!this.container)return;this.gridEl=document.getElementById(`${e}Grid`),this.loadingEl=document.getElementById(`${e}Loading`),this.emptyEl=document.getElementById(`${e}Empty`)}initEventListeners(){let{containerId:e,showPagination:t,showCheckboxes:i}=this.config;if(document.getElementById(`${e}RefreshBtn`)?.addEventListener("click",()=>this.load()),t){let s=document.getElementById(`${e}PrevPage`),a=document.getElementById(`${e}NextPage`),r=document.getElementById(`${e}PageSize`);s?.addEventListener("click",()=>this.goToPage(this.currentPage-1)),a?.addEventListener("click",()=>this.goToPage(this.currentPage+1)),r?.addEventListener("change",()=>{this.config.limit=parseInt(r.value,10)||d,this.currentPage=1,this.load()})}if(i){let s=document.getElementById(`${e}SelectAll`);s?.addEventListener("change",()=>{if(s.checked)this.events.forEach((a)=>this.selectedIds.add(Number(a.Id)));else this.selectedIds.clear();this.updateCardCheckboxes(),this.config.onSelectionChange?.(this.selectedIds)}),this.initToolbarButtons()}}initToolbarButtons(){let{containerId:e}=this.config,t=document.getElementById(`${e}ArchiveBtn`),i=document.getElementById(`${e}UnarchiveBtn`),n=document.getElementById(`${e}ExportBtn`),s=document.getElementById(`${e}DeleteBtn`);t?.addEventListener("click",()=>this.archiveSelected(!0)),i?.addEventListener("click",()=>this.archiveSelected(!1)),n?.addEventListener("click",()=>this.exportSelected()),s?.addEventListener("click",()=>this.deleteSelected())}async load(){if(this.isLoading||!this.gridEl)return;this.isLoading=!0,this.showLoading(!0),this.clearCards();try{let{rows:e,total:t}=await this.fetchEvents();this.events=e,this.totalEvents=t,this.renderCards(),this.updatePagination(),this.config.onEventsLoaded?.(e,t)}catch(e){console.error("[event-cards] Failed to load events:",e)}finally{this.showLoading(!1),this.isLoading=!1}}async fetchEvents(){let{monitorId:e,limit:t,filterParams:i,showPagination:n}=this.config,s=new URLSearchParams({view:"request",request:"events",task:"query",order:"desc",sort:"Id",limit:String(t||d)});if(n){let r=(this.currentPage-1)*(t||d);s.set("offset",String(r))}if(e)s.set("filter[Query][terms][0][attr]","MonitorId"),s.set("filter[Query][terms][0][op]","="),s.set("filter[Query][terms][0][val]",String(e));if(i)Object.entries(i).forEach(([r,o])=>{s.set(r,o)});let a=await fetch(`?${s.toString()}`,{credentials:"include"});if(!a.ok)throw Error(`HTTP ${a.status}`);return a.json()}renderCards(){if(!this.gridEl)return;if(this.events.length===0){this.emptyEl?.classList.remove("hidden");return}this.emptyEl?.classList.add("hidden");let e=this.events.map((t)=>this.renderCard(t)).join("");this.gridEl.insertAdjacentHTML("beforeend",e),this.attachCardEventListeners()}renderCard(e){let{showMonitorName:t,showCheckboxes:i,monitorId:n}=this.config,s=e.Id,a=n?this.getMonitorFilterQuery(n):"",r=e.StartDateTime?this.formatDateTime(e.StartDateTime):"-",o=e.EndDateTime?this.formatDateTime(e.EndDateTime):"-",v=e.Length||"-",g=Number(e.TotScore)||0,u=Number(e.AvgScore)||0,c=Number(e.MaxScore)||0,p=c>=75?"text-error":c>=50?"text-warning":"text-success",f=this.selectedIds.has(Number(s)),E=e.Storage||"Default",b=e.DiskSpace||"-",y=e.imgHtml?`<a href="?view=event&eid=${s}${a}" class="block aspect-video bg-base-300 rounded-lg overflow-hidden mb-2 [&>img]:w-full [&>img]:h-full [&>img]:object-cover">${this.resizeThumbnail(e.imgHtml)}</a>`:"",S=i?`<input type="checkbox" class="checkbox checkbox-sm event-checkbox" data-eid="${s}" ${f?"checked":""} />`:"",h=e.Monitor||e.MonitorName,w=t&&h?`<div class="text-xs opacity-50 truncate">${this.escapeHtml(h)}</div>`:"",I=e.Archived===1||e.Archived===!0?'<span class="badge badge-xs badge-secondary" title="Protected from automatic deletion">Archived</span>':"";return`
      <div class="card bg-base-300 shadow-sm hover:shadow-md transition-shadow event-card" data-eid="${s}">
        <div class="card-body p-3">
          ${y}
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-start gap-2 flex-1 min-w-0">
              ${S}
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <a href="?view=event&eid=${s}${a}" class="font-medium text-sm link link-hover link-primary truncate">
                    ${this.escapeHtml(String(e.Name||`Event ${s}`))}
                  </a>
                  <span class="text-xs opacity-40">#${s}</span>
                </div>
                ${w}
              </div>
            </div>
            <button type="button" class="btn btn-ghost btn-xs btn-square text-error delete-event-btn" data-eid="${s}" title="Delete">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
              </svg>
            </button>
          </div>
          
          <!-- Time info -->
          <div class="grid grid-cols-2 gap-x-2 mt-2 text-xs opacity-70">
            <div title="Start Time"><span class="opacity-60">Start:</span> ${r}</div>
            <div title="End Time"><span class="opacity-60">End:</span> ${o}</div>
          </div>
          
          <!-- Cause and duration badges -->
          <div class="flex flex-wrap gap-1 mt-2">
            <span class="badge badge-xs badge-outline">${this.escapeHtml(e.Cause||"Unknown")}</span>
            <span class="badge badge-xs badge-outline">${v}s</span>
            ${I}
          </div>
          
          <!-- Scores with labels and tooltips -->
          <div class="grid grid-cols-3 gap-1 mt-2 text-xs">
            <div class="tooltip tooltip-bottom" data-tip="Average motion detection score across all alarm frames">
              <span class="opacity-60">Avg:</span> <span class="font-medium">${u}</span>
            </div>
            <div class="tooltip tooltip-bottom" data-tip="Maximum motion detection score in any single frame">
              <span class="opacity-60">Max:</span> <span class="font-medium ${p}">${c}</span>
            </div>
            <div class="tooltip tooltip-bottom" data-tip="Sum of all motion detection scores">
              <span class="opacity-60">Total:</span> <span class="font-medium">${g}</span>
            </div>
          </div>
          
          <!-- Frame counts -->
          <div class="grid grid-cols-2 gap-1 mt-1 text-xs opacity-70">
            <div title="Total Frames">
              <span class="opacity-60">Frames:</span> ${e.Frames||0}
            </div>
            <div title="Alarm Frames">
              <span class="opacity-60">Alarms:</span> ${e.AlarmFrames||0}
            </div>
          </div>
          
          <!-- Storage info -->
          <div class="grid grid-cols-2 gap-1 mt-1 text-xs opacity-70">
            <div title="Storage Area">
              <span class="opacity-60">Storage:</span> ${this.escapeHtml(E)}
            </div>
            <div title="Disk Space Used">
              <span class="opacity-60">Size:</span> ${this.escapeHtml(b)}
            </div>
          </div>
        </div>
      </div>
    `}attachCardEventListeners(){if(!this.gridEl)return;if(this.gridEl.querySelectorAll(".delete-event-btn").forEach((e)=>{e.addEventListener("click",(t)=>this.handleDeleteEvent(t))}),this.config.showCheckboxes)this.gridEl.querySelectorAll(".event-checkbox").forEach((e)=>{e.addEventListener("change",(t)=>this.handleCheckboxChange(t))})}async handleDeleteEvent(e){e.preventDefault(),e.stopPropagation();let i=e.currentTarget.dataset.eid;if(!i)return;if(!await l("Are you sure you want to delete this event? This action cannot be undone.","Delete Event"))return;try{await this.deleteEvents([Number(i)]),this.removeCardFromDom(i)}catch(s){console.error("[event-cards] Failed to delete event:",s)}}handleCheckboxChange(e){let t=e.target,i=Number(t.dataset.eid);if(t.checked)this.selectedIds.add(i);else this.selectedIds.delete(i);this.updateSelectAllCheckbox(),this.config.onSelectionChange?.(this.selectedIds)}updateCardCheckboxes(){if(!this.gridEl)return;this.gridEl.querySelectorAll(".event-checkbox").forEach((e)=>{let t=e,i=Number(t.dataset.eid);t.checked=this.selectedIds.has(i)})}updateSelectAllCheckbox(){let{containerId:e}=this.config,t=document.getElementById(`${e}SelectAll`);if(!t)return;let i=this.events.length>0&&this.events.every((s)=>this.selectedIds.has(Number(s.Id))),n=this.selectedIds.size>0;t.checked=i,t.indeterminate=n&&!i}async archiveSelected(e){if(this.selectedIds.size===0)return;let t=e?"archive":"unarchive",i=new URLSearchParams({request:"events",task:t});this.selectedIds.forEach((n)=>i.append("eids[]",String(n)));try{let n=await fetch(`?${i.toString()}`,{credentials:"include"});if(!n.ok)throw Error(`HTTP ${n.status}`);await this.load()}catch(n){console.error(`[event-cards] Failed to ${t} events:`,n)}}exportSelected(){if(this.selectedIds.size===0)return;let e=Array.from(this.selectedIds).join(",");window.location.href=`?view=export&eids=${e}`}async deleteSelected(){if(this.selectedIds.size===0)return;if(!await l(`Are you sure you want to delete ${this.selectedIds.size} event(s)? This action cannot be undone.`,"Delete Events"))return;try{await this.deleteEvents(Array.from(this.selectedIds)),this.selectedIds.clear(),await this.load()}catch(t){console.error("[event-cards] Failed to delete events:",t)}}async deleteEvents(e){let t=new URLSearchParams({request:"events",task:"delete"});e.forEach((n)=>t.append("eids[]",String(n)));let i=await fetch(`?${t.toString()}`,{credentials:"include"});if(!i.ok)throw Error(`HTTP ${i.status}`)}removeCardFromDom(e){if(this.gridEl?.querySelector(`.event-card[data-eid="${e}"]`)?.remove(),this.events=this.events.filter((i)=>String(i.Id)!==e),this.selectedIds.delete(Number(e)),this.events.length===0)this.emptyEl?.classList.remove("hidden")}goToPage(e){let t=Math.ceil(this.totalEvents/(this.config.limit||d));if(e<1||e>t)return;this.currentPage=e,this.load()}updatePagination(){if(!this.config.showPagination)return;let{containerId:e,limit:t}=this.config,i=t||d,n=Math.max(1,Math.ceil(this.totalEvents/i)),s=document.getElementById(`${e}PrevPage`),a=document.getElementById(`${e}NextPage`),r=document.getElementById(`${e}PageInfo`),o=document.getElementById(`${e}Total`);if(s)s.disabled=this.currentPage<=1;if(a)a.disabled=this.currentPage>=n;if(r)r.textContent=`${this.currentPage} / ${n}`;if(o)o.textContent=`${this.totalEvents} event(s)`}showLoading(e){this.loadingEl?.classList.toggle("hidden",!e)}clearCards(){this.gridEl?.querySelectorAll(".event-card").forEach((e)=>e.remove()),this.emptyEl?.classList.add("hidden")}getMonitorFilterQuery(e){return`&filter[Query][terms][0][attr]=MonitorId&filter[Query][terms][0][op]=%3d&filter[Query][terms][0][val]=${e}`}formatDateTime(e){try{return new Date(e).toLocaleString(void 0,{month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"})}catch{return e}}escapeHtml(e){let t=document.createElement("div");return t.textContent=e,t.innerHTML}resizeThumbnail(e){let t=this.config.thumbnailWidth||m,i=e.replace(/\swidth="\d+"/,` width="${t}"`).replace(/\sheight="\d+"/,' height="auto"');return i=i.replace(/(src="[^"]*)(width)=(\d+)/,`$1$2=${t}`),i=i.replace(/(src="[^"]*)(height)=(\d+)/,(n,s,a,r)=>{return`${s}${a}=0`}),i}getSelectedIds(){return new Set(this.selectedIds)}clearSelection(){this.selectedIds.clear(),this.updateCardCheckboxes(),this.updateSelectAllCheckbox(),this.config.onSelectionChange?.(this.selectedIds)}}
export{x as a};
