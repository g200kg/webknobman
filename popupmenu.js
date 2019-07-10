class PopupMenu {
  constructor(x, y, items, callback){
    this.menu = document.createElement("div");
    this.menu.classList.add("popupmenu");
    this.menu.style.left = (x + 8) + "px";
    this.menu.style.top = y + "px";
    let s = "";
    for(let i = 0; i < items.length; ++i){
      s += `<button class='popupmenu-item' id='popupmenu-item-${i}'>${items[i]}</button>`;
    }
    this.menu.innerHTML = s;
    this.callback = callback;
    window._popupmenu = this;
    document.body.style.position = "relative";
    document.body.appendChild(this.menu);
    document.addEventListener("mousedown",PopupMenu.DocMouseDown);
  }
  Close(){
    if(window._popupmenu){
      document.body.removeChild(window._popupmenu.menu);
      window._popupmenu.menu.style.display="none";
      window._popupmenu = null;
    }
  }
  static DocMouseDown(event){
    if(event.target.className == "popupmenu-item"){
      window._popupmenu.callback(event.target.id.substring(15));
    }
    window._popupmenu.Close();
    document.removeEventListener("mousedown", PopupMenu.DocMouseDown);
  }
}
function PopupMenuClick(){
  console.log("click:", this)
}
