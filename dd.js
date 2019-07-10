function DDInit(){
  function sendEvent(elm,ev){
    const event = document.createEvent("HTMLEvents");
    event.initEvent(ev,true,true);
    elm.dispatchEvent(event);
  }
  let s = document.getElementsByClassName("select");
  for(let i = 0; i < s.length; ++i){
    const elm = s[i];
    elm.isOpen = 0;
    elm.cur = document.createElement("div");
    elm.appendChild(elm.cur);
    elm.arrow = document.createElement("div");
    elm.arrow.innerHTML = "&#x23f7;";//"<svg width='16px' height='16px'><path d='M4 4 H8 L8 12 L4 4 Z' fill='#000000'/></svg>";
    elm.arrow.style.position = "absolute";
    elm.arrow.style.top = "0px";
    elm.arrow.style.right = "0px";
    elm.appendChild(elm.arrow);
    Object.defineProperty(elm, "selectedIndex", {
      get(){ return elm._selectedIndex;},
      set(x){
        elm._selectedIndex = x;
        const opts = elm.getElementsByClassName("option");
        const dup = opts[x].cloneNode(true);
        dup.classList.remove("option");
        elm.replaceChild(dup,elm.cur);
        elm.cur = dup;
        elm.cur.style.display = "block";
        elm.value = dup.innerText;
      }
    });
    elm._selectedIndex = 0;
    elm.close = ()=>{
      const opts = elm.getElementsByClassName("option");
      for(let i = 0; i < opts.length; ++i){
        const st = opts[i].style;
        st.left="0px";
        st.top = "0px";
        st.display = "none";
        elm.style.overflow = "hidden";
      }
      elm.isOpen = 0;
      window.removeEventListener("mousedown",elm.close);
    };
    elm.open = ()=>{
      const opts = elm.getElementsByClassName("option");
      let y = elm.offsetHeight;
      for(let i = 0; i < opts.length; ++i){
        const opt = opts[i];
        const st = opt.style;
        st.left="-1px";
        st.top = y + "px";
        st.display = "block";
        elm.style.overflow = "visible";
        y += opt.offsetHeight-1;
      }
      window.addEventListener("mousedown",elm.close);
      elm.isOpen = 1;
    }
    elm.addEventListener("mousedown",(ev)=>{
      if(elm.isOpen){
        elm.close();
        elm.isOpen = 0;
      }
      else{
        elm.open();
        elm.isOpen = 1;
      }
      ev.stopPropagation();
    });
    const opts = elm.getElementsByClassName("option");
    for(let i = 0; i < opts.length; ++i){
      const opt = opts[i];
      opt.addEventListener("mouseup",()=>{
        if(elm.isOpen){
          elm.selectedIndex = i;
          sendEvent(elm,"change");
          elm.close();
        }
      },true);
      opt.addEventListener("mousedown",(ev)=>{
        ev.stopPropagation();
      });
    }
  }
  test = document.getElementById("test");
}
