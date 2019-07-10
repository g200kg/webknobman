class ColorMapControl{
    constructor(elem){
        this.elem = elem;
        elem.innerHTML = `<div style="width:250px;height:240px;position:relative">
                <div style="width:100px;float:left">Color:</div><input style="width:150px;height:22px;background:#ffffff"/>
                <canvas width="140" height="120" style="position:absolute;top:30px;left:0px"></canvas>
                <div style="position:absolute;top:23px;left:150px">H :</div>
                <div style="position:absolute;top:23px;left:180px"><input size="3"/></div>
                <div style="position:absolute;top:46px;left:150px">L :</div>
                <div style="position:absolute;top:46px;left:180px"><input size="3"/></div>
                <div style="position:absolute;top:69px;left:150px">S :</div>
                <div style="position:absolute;top:69px;left:180px"><input size="3"/></div>
                <div style="position:absolute;top:92px;left:150px">R :</div>
                <div style="position:absolute;top:92px;left:180px"><input size="3"/></div>
                <div style="position:absolute;top:115px;left:150px">G :</div>
                <div style="position:absolute;top:115px;left:180px"><input size="3"/></div>
                <div style="position:absolute;top:138px;left:150px">B :</div>
                <div style="position:absolute;top:138px;left:180px"><input size="3"/></div>
                <button style="position:absolute;top:170px;left:0px;width:23px;height:16px;background:#000000"></button>
                <button style="position:absolute;top:170px;left:25px;width:23px;height:16px;background:#000080"></button>
                <button style="position:absolute;top:170px;left:50px;width:23px;height:16px;background:#008000"></button>
                <button style="position:absolute;top:170px;left:75px;width:23px;height:16px;background:#008080"></button>
                <button style="position:absolute;top:170px;left:100px;width:23px;height:16px;background:#800000"></button>
                <button style="position:absolute;top:170px;left:125px;width:23px;height:16px;background:#800080"></button>
                <button style="position:absolute;top:170px;left:150px;width:23px;height:16px;background:#808000"></button>
                <button style="position:absolute;top:170px;left:175px;width:23px;height:16px;background:#808080"></button>
                <button style="position:absolute;top:190px;left:0px;width:23px;height:16px;background:#c0c0c0"></button>
                <button style="position:absolute;top:190px;left:25px;width:23px;height:16px;background:#0000ff"></button>
                <button style="position:absolute;top:190px;left:50px;width:23px;height:16px;background:#00ff00"></button>
                <button style="position:absolute;top:190px;left:75px;width:23px;height:16px;background:#00ffff"></button>
                <button style="position:absolute;top:190px;left:100px;width:23px;height:16px;background:#ff0000"></button>
                <button style="position:absolute;top:190px;left:125px;width:23px;height:16px;background:#ff00ff"></button>
                <button style="position:absolute;top:190px;left:150px;width:23px;height:16px;background:#ffff00""></button>
                <button style="position:absolute;top:190px;left:175px;width:23px;height:16px;background:#ffffff""></button>
                <button style="position:absolute;top:210px;left:0px;width:23px;height:16px;background:#000000"></button>
                <button style="position:absolute;top:210px;left:25px;width:23px;height:16px;background:#000000"></button>
                <button style="position:absolute;top:210px;left:50px;width:23px;height:16px;background:#000000"></button>
                <button style="position:absolute;top:210px;left:75px;width:23px;height:16px;background:#000000"></button>
                <button style="position:absolute;top:210px;left:100px;width:23px;height:16px;background:#000000"></button>
                <button style="position:absolute;top:210px;left:125px;width:23px;height:16px;background:#000000"></button>
                <button style="position:absolute;top:210px;left:150px;width:23px;height:16px;background:#000000""></button>
                <button style="position:absolute;top:210px;left:175px;width:23px;height:16px;background:#000000""></button>
            </div>`;
        this.cv = elem.getElementsByTagName("canvas")[0];
        this.inputs = elem.getElementsByTagName("input");
        this.inputs[0].addEventListener("change",()=>{this.SetColStr(this.inputs[0].value)});
        for(let i=1;i<=3;++i)
            this.inputs[i].addEventListener("change",()=>{this.SetHls(this.inputs[1].value,this.inputs[2].value,this.inputs[3].value)});
        for(let i=4;i<=6;++i)
            this.inputs[i].addEventListener("change",()=>{this.SetRgb(this.inputs[4].value,this.inputs[5].value,this.inputs[6].value)});
        const btns = elem.getElementsByTagName("button");
        for(let x = 0; x < btns.length; ++x){
            btns[x].addEventListener("click",(event)=>{
                this.SetColStr(event.target.style.backgroundColor);
            });
            btns[x].addEventListener("contextmenu",(event)=>{
              new PopupMenu(event.pageX, event.pageY, ["Register"],(n)=>{
                if(n==0){
                  console.log(this)
                  btns[x].style.backgroundColor = this.col.GetColStr();
                }
              });
              event.preventDefault();
            });
        }
        this.ctxMap = this.cv.getContext("2d");
        this.imgdatMap = this.ctxMap.getImageData(0, 0, 120, 120);
        const col = new Col(0, 0, 0);
        let p = 0;
        for (let y = 0; y < 120; ++y) {
            for (let x = 0; x < 120; ++x) {
                col.SetHls(x * 2, 120, 238 - y * 2);
                this.imgdatMap.data[p] = col.r;
                this.imgdatMap.data[p + 1] = col.g;
                this.imgdatMap.data[p + 2] = col.b;
                this.imgdatMap.data[p + 3] = 255;
                p += 4;
            }
        }
        this.ctxMap.putImageData(this.imgdatMap, 0, 0);
        this.imgdatBar = this.ctxMap.getImageData(130, 0, 10, 120);
        this.cv.onmousedown = this.MouseDown.bind(this);
        this.MouseMoveBind = this.MouseMove.bind(this);
        this.MouseUpBind = this.MouseUp.bind(this);
        this.col = new Col(0, 0, 0,);
        this.drag = 0;
        this.Disp();
    }
    GetXY(ev){
        const rc = this.cv.getBoundingClientRect();
        const px = Math.max(0, Math.min(120, Math.floor(ev.clientX - rc.left)));
        const py = 120 - Math.max(0, Math.min(120, Math.floor(ev.clientY - rc.top)));
        return {x:px, y:py};
    }
    MouseDown(ev){
        if(ev.buttons==1){
            document.addEventListener("mousemove",this.MouseMoveBind);
            document.addEventListener("mouseup",this.MouseUpBind);
            const pos = this.GetXY(ev);
            this.drag = (pos.x >= 120) ? 2 : 1;
            this.MouseMove(ev);
        }
    }
    MouseMove(ev){
        const p = this.GetXY(ev);
        switch(this.drag){
        case 1:
            this.SetHls(p.x * 2, this.col.l, p.y * 2);
            break;
        case 2:
            this.SetHls(this.col.h, p.y * 2, this.col.s);
            break;
        }
    }
    MouseUp(ev){
        this.drag = 0;
        document.removeEventListener("mousemove", this.MouseMoveBind);
        document.removeEventListener("mosueup", this.MouseUpBind);
    }
    SetHls(h,l,s){
        this.col.SetHls(h,l,s);
        this.Disp();
        this.SendEvent();
    }
    SetRgb(r,g,b){
        this.col.SetRgb(r,g,b);
        this.Disp();
        this.SendEvent();
    }
    SetCol(c){
        this.col = c;
        this.Disp();
        this.SendEvent();
    }
    SetColStr(s){
        this.col.SetColStr(s);
        this.Disp();
        this.SendEvent();
    }
    ColorBar(c){
        const col = new Col(0, 0, 0);
        let p = 0;
        for (let y = 0; y < 120; ++y) {
            col.SetHls(c.h, (119 - y) * 2, c.s);
            for (let x = 0; x < 10; ++x) {
                this.imgdatBar.data[p] = col.r;
                this.imgdatBar.data[p + 1] = col.g;
                this.imgdatBar.data[p + 2] = col.b;
                this.imgdatBar.data[p + 3] = 255;
                p += 4;
            }
        }
        this.ctxMap.putImageData(this.imgdatBar, 130, 0);
    }
    SendEvent(){
        const e = document.createEvent("HTMLEvents");
        e.initEvent("change",true,true);
        this.elem.dispatchEvent(e);
    }
    Disp(){
        this.ctxMap.putImageData(this.imgdatMap, 0, 0);
        this.ColorBar(this.col);
        this.ctxMap.fillStyle="#ffffff";
        this.ctxMap.fillRect(this.col.h*0.5-10, 119-this.col.s*0.5, 20, 2);
        this.ctxMap.fillRect(this.col.h*0.5-1, 119-this.col.s*0.5-10, 2, 20);
        this.ctxMap.clearRect(120,0,10,120);
        this.ctxMap.beginPath();
        const py = 120-this.col.l*0.5;
        this.ctxMap.moveTo(130, py);
        this.ctxMap.lineTo(120, py + 5);
        this.ctxMap.lineTo(120, py - 5);
        this.ctxMap.closePath();
        this.ctxMap.fill();
        this.inputs[0].style.color= ((this.col.r * 3 + this.col.g * 6 + this.col.b) > 1280) ? "#000000":"#ffffff"; 
        this.inputs[0].style.background=this.col.GetColStr();
        this.inputs[0].value = this.col.GetColStr();
        this.inputs[1].value = Math.min(240,this.col.h|0);
        this.inputs[2].value = Math.min(240,this.col.l|0);
        this.inputs[3].value = Math.min(240,this.col.s|0);
        this.inputs[4].value = Math.min(255,this.col.r|0);
        this.inputs[5].value = Math.min(255,this.col.g|0);
        this.inputs[6].value = Math.min(255,this.col.b|0);
    }
}
