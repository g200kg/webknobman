<?php
include 'secret/galleryvars.php';
$dblink=null;
function dbinit(){
  global $dblink;
  $dblink = new mysqli($dburl, $dbuser, $dbpass, $dbname);
  if ($dblink->connect_error) {
    echo $dblink->connect_error;
    exit();
  }
  $dblink->set_charset("utf8");
}
function dbclose(){
  global $dblink;
  $dblink->close();
}
function getdelkey($id){
  global $dblink;
  dbinit();
  $r = $dblink->query("SELECT delkey FROM knobgallery_list WHERE id=".strval($id).";");
  $row = $r->fetch_assoc();
  dbclose();
  return $row["delkey"];
}
function updatetag($id,$tag){
  global $dblink;
  dbinit();
  $cmd="UPDATE knobgallery_list SET tags=\"" . addslashes($tag) . "\" WHERE id=" . strval($id) .";";
  $dblink->query($cmd);
  dbclose();
}
function delfile($id){
  global $dblink;
  dbinit();
  $cmd = "DELETE FROM knobgallery_list WHERE id=" . strval($id) . " LIMIT 1;";
  $dblink->query($cmd);
  dbclose();
}
function addfile($lic,$date,$author,$file,$delkey,$com,$tags) {
  global $dblink;
  dbinit();
  $cmd = "INSERT INTO knobgallery_list (license,date,author,file,delkey,comment,tags) VALUES ("
    ."'".$lic."',"
    ."'".$date."',"
    ."'".addslashes($author)."',"
    ."'".$file."',"
    ."'".addslashes($delkey)."',"
    ."'".addslashes($com)."',"
    ."'".addslashes($tags)."'"
  .");";
  $dblink->query($cmd);
  $res = $dblink->insert_id;
  dbclose();
  return "./data/gal/".strval($res).".knob";
}
function makeimg($path) {
  if(file_exists($path)) {
    $imgpath=str_replace(".knob",".png",$path);
    copy($path,$imgpath);
    $fp=fopen($imgpath,"r+b");
    fwrite($fp,"\x89\x50\x4e\x47\x0d\x0a\x1a\x0a");
    fclose($fp);
  }
}



/*
if($_GET["m"]==="sql") {
  dbinit();
  echo "sql:".$_GET["s"]."<br/>";
  $r = $dblink->query($_GET["s"]);
  dbclose();
  while($row = $r->fetch_assoc()){
    echo $row["num"] . " : " . $row["file"] . "<br/>";
  }
  die;
}
*/
$s=file_get_contents('php://input');
if(substr($s,0,1)=="{"){
  $obj = json_decode($s,true);
  switch($obj["cmd"]){
  case "tag":
    updatetag($obj["id"], $obj["tag"]);
    echo '{"result":true, "mess":"Complete"}';
    die;
  case "del":
    $id = $obj["id"];
    $dk = getdelkey($id);
    if($obj["key"] === "" or ($obj["key"] != $masterkey and $obj["key"] != $dk)) {
      echo '{"result":false, "mess":"Key Error"}';
      die;
    }
    if($id > 0){
      $r = delfile($id);
      $fname = "./data/gal/" . strval($id) . ".knob";
      $fname2 = $fname . "$";
      @rename($fname, $fname2);
      $fname = str_replace(".knob", ".png", $fname);
      $fname2 = $fname . "$";
      @rename($fname, $fname2);
      echo '{"result":true, "mess":"Complete"}';
      die;
    }
    echo '{"result":false, "mess":"Id Error"}';
    die;
  default:
    echo '{"result":false, "mess:"Cmd Unknown"}';
    die;
  }
}

if($_POST["m"]==="tag"){
  $id = intval($_POST["n"]);
  $tag = $_POST["t"];
  updatetag($id,$tag);
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: ./gallery.php?m=p&p=".strval($id));
  die;
}
if($_POST["m"]==="de"){
  $id = intval($_POST["n"]);
  $dk = getdelkey($id);
  if($_POST[dk] === "" or ($_POST[dk] != $masterkey and $_POST[dk] != $dk)) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ./gallery.php?r=".base64_encode("DelKey password error."));
    die;
  }
  if($id > 0){
    $r=delfile($id);
    $fname="./data/gal/".$id.".knob";
    $fname2=$fname."$";
    @rename($fname,$fname2);
    $fname=str_replace(".knob",".png",$fname);
    $fname2=$fname."$";
    @rename($fname,$fname2);
      header("HTTP/1.1 301 Moved Permanently");
    header("Location: ./gallery.php?r=".base64_encode("Delete Complete."));
    die;
  }
  else{
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ./gallery.php?r=".base64_encode("Item ID is not properly specified."));
    die;
  }
}
if($_POST["m"]==="up"){
  $tempname = $_FILES["file"]["tmp_name"];
  $origname = $_FILES["file"]["name"];
  $filesize = $_FILES["file"]["size"];
  $com=$_POST[co];
  $f=pathinfo($origname);
  $ext=$f["extension"];
  $ext=strtolower($ext);
  $err="";
  $replace = array(
    ' ' => '_',
    '/' => '',
    '\\' => '',
    ':' => '',
    ',' => '',
    ';' => '',
    '*' => '',
    '?' => '',
    '\"' => '\'',
    '\'' => '\'',
    '<' => '',
    '>' => '',
    '|' => '',
  );
  $origname = str_replace(array_keys($replace), array_values($replace), $origname);
  if(!$tempname) {
    $err="Upload Failed.<br/> No file specified.";
  } elseif(($filesize/(1024*1024)) > 10) {
    $err="Upload Failed.<br/> File size should be <10MB";
  } elseif($ext!="knob") {
    $err="Upload Failed.<br/> Specified file is no knob-file.";
  }
  if($err!="") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ./gallery.php?r=".base64_encode($err));
    die;
  }
  $ndate = date("Y-m-d H:i:s");
  if($_POST["k"] != "") {
    $ndelkey = $_POST["k"];
  }
  $topath=addfile($_POST["l"]
    ,date("Y-m-d H:i:s")
    ,$_POST["a"]
    ,$origname
    ,$_POST["k"]
    ,$_POST["c"]
    ,$_POST["p"].$_POST["t"]);
  move_uploaded_file($tempname,$topath);
  makeimg($topath);
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: ./gallery.php?r=".base64_encode("Upload Complete."));
}
if($_GET["m"]==="get") {
  $id=intval($_GET["n"]);
  if($id!=0)
    $fn="./data/gal/".$id.".knob";
  else
    $fn="./data/".$_GET["v"];
  $name=$_GET["f"];
  if(strpos($fn,"..")!=false)
    $dat="";
  else
    $dat=file_get_contents($fn);
  switch($_GET["t"]) {
  case bin:
    if($name=="")
      $name=pathinfo($fn,PATHINFO_BASENAME);
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.filesize($fn));
    header('Content-Disposition: attachment; filename="'.$name.'"');
    break;
  case img:
    header('Content-Type: image/png');
    $dat=substr_replace($dat,"\x89\x50\x4e\x47\x0d\x0a\x1a\x0a",0,8);
    break;
  case b64:
    $dat=base64_encode($dat);
    break;
  }
  echo $dat;
  die;
}
if($_GET["m"]==="list") {
  dbinit();
  $r = $dblink->query("SELECT id,license,date,author,file,comment,tags FROM knobgallery_list ORDER BY id desc");
  dbclose();
  $loop = 0;
  echo "[";
  while($row = $r->fetch_assoc()){
    if($loop!=0)
      echo ",\n";
    $rauth = str_replace("\"","\\\"",$row["author"]);
    $rfile = str_replace("\"","\\\"",$row["file"]);
    $rcomment = str_replace("\"","\\\"",$row["comment"]);
    $rtags = str_replace("\"","\\\"",$row["tags"]);
    $fn = "./data/gal/".$row["id"].".knob";
    if(file_exists($fn))
      $size=(string)filesize($fn);
    else
      $size="0";
    echo "{\"id\":\"" . $row["id"] . "\","
      . "\"license\":\"" . $row["license"] . "\","
      . "\"date\":\"" . $row["date"] . "\","
      . "\"author\":\"" . $rauth . "\","
      . "\"file\":\"" . $rfile . "\","
      . "\"size\":\"".$size."\","
      . "\"comment\":\"" . $rcomment . "\","
      . "\"tags\":\"" . $rtags . "\"}";
    ++$loop;
  }
  echo "]";
  die;
}
?>

<!doctype html>
<head>
<link href="https://fonts.googleapis.com/css?family=Cinzel+Decorative&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Lato&display=swap" rel="stylesheet">
<script>

let items=[];
let lazyitems=[];
let bgcolor="#445566";

function add(item){
  let url=`./gallery.php?m=add&n=${item.num}&l=${item.lic}&d=${item.date}&a=${item.auth}&f=${item.file}&k=${item.key}&c=${item.com}&t=${item.tags}`;
  console.log(url);
  fetch(url)
    .then(res=>res.text())
    .then(text=>console.log(text));
}
function Import(){
  s=document.getElementById("json").value;
  o=JSON.parse(s);
  console.log(o);
  s="";
  for(i=o.length-1;i>=0;--i){
    item=o[i];
    auth=item.auth.replace(/'/g,"''");
    file=item.file.replace(/'/g,"''");
    key=item.key.replace(/'/g,"''");
    com=item.com.replace(/'/g,"''");
    tags=item.tags.replace(/'/g,"''");
    s+=`(${item.num},'${item.lic}','${item.date}','${auth}','${file}','${key}','${com}','${tags}')`;
    if(i>0)
      s+=',\n';
  }
  s+=";\n";
  document.getElementById("json").value = s;
}

function lazyload() {
  const wH = window.innerHeight;
  const offset = 100;
  for(let i=lazyitems.length-1;i>=0;--i) {
    const boundingRect = lazyitems[i].img.getBoundingClientRect();
    const yPositionTop = boundingRect.top - wH;
    const yPositionBottom = boundingRect.bottom;
    const divdisp = lazyitems[i].img.parentNode.parentNode.style.display;

    if(divdisp != "none" && yPositionTop <= offset && yPositionBottom >= -offset) {
      if (!lazyitems[i].img.src) {
        lazyitems[i].img.src = lazyitems[i].url;
        lazyitems.splice(i,1);
      }
    }
  }
  if(lazyitems.length>0)
    setTimeout(lazyload,300);
}

function GetGalleryImgUrl(id){
  return `./data/gal/${id}.png`;
}
function BgColor(){
  let c=document.getElementById("bgcolor").value;
  for(let i=0;i<items.length;++i){
    const e=items[i].div;
    items[i].div.style.background=c;
  }
  document.getElementById("infoknob").style.background=c;
}
function Search(){
  const type=document.getElementById("searchtype");
  const typeword=type.options[type.selectedIndex].value;
  let words=(document.getElementById("search").value.toLowerCase()).split(" ");
  if(typeword)
    words.unshift(typeword);
  for(let i=items.length-1;i>=0;--i){
    const item=items[i].item;
    let find=true;
    for(let j=words.length-1;j>=0;--j){
      const w=words[j];
      find=item.file.toLowerCase().indexOf(w)>=0
        || item.author.toLowerCase().indexOf(w)>=0
        || item.comment.toLowerCase().indexOf(w)>=0
        || item.tags.toLowerCase().indexOf(w)>=0
        || item.license.toLowerCase().indexOf(w)>=0;
      if(!find)
        break;
    }
    if(find){
      items[i].div.style.display="block";
    }
    else{
      items[i].div.style.display="none";
    }
  }
}
function UploadLicChange(elm){
  const v=elm.options[elm.selectedIndex].value;
  const img=document.getElementById("uploadlicimg");
  const link=document.getElementById("uploadliclink");
  const txt=document.getElementById("uploadlictxt");
  img.src=`./img/${v}.png`;
  switch(v){
  case "CC0":
    link.href="https://creativecommons.org/publicdomain/zero/1.0/";
    txt.innerHTML="Creative Commons CC0 1.0";
    break;
  case "CC-BY-4.0":
    link.href="https://creativecommons.org/licenses/by/4.0/";
    txt.innerHTML="Creative Commons CC-BY 4.0";
    break;
  case "CC-BY-SA-4.0":
    link.href="https://creativecommons.org/licenses/by-sa/4.0/";
    txt.innerHTML="Creative Commons CC-BY-SA 4.0";
    break;
  case "CC-BY-ND-4.0":
    link.href="https://creativecommons.org/licenses/by-nd/4.0/";
    txt.innerHTML="Creative Commons CC-BY-ND 4.0";
    break;
  case "CC-BY-NC-4.0":
    link.href="https://creativecommons.org/licenses/by-nc/4.0/";
    txt.innerHTML="Creative Commons CC-BY-NC 4.0";
    break;
  case "CC-BY-NC-SA-4.0":
    link.href="https://creativecommons.org/licenses/by-nc-sa/4.0/";
    txt.innerHTML="Creative Commons CC-BY-NC-SA 4.0";
    break;
  case "CC-BY-NC-ND-4.0":
    link.href="https://creativecommons.org/licenses/by-nc-nd/4.0/";
    txt.innerHTML="Creative Commons CC-BY-NC-ND 4.0";
    break;
  }
}
function UploadOpen(){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("uploadpane");
  base.style.top="0";
  pane.style.top="50%";
  pane.style.transform="translateY(-50%)";
}
function UploadCancel(){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("uploadpane");
  base.style.top="100%";
  pane.style.top="100%";
  pane.style.transform="translateY(0%)";
}
function ConfirmOpen(str){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("confirmpane");
  document.getElementById("confirmtxt").innerHTML=str;
  base.style.top="0";
  pane.style.top="50%";
  pane.style.transform="translateY(-50%)";
}
function ConfirmCancel(){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("confirmpane");
  base.style.top="100%";
  pane.style.top="100%";
  pane.style.transform="translateY(0%)";
}
function ConfirmOk(){
  ConfirmCancel();
}
messageCloseUrl="";
function MessageOpen(str,url){
  messageCloseUrl=url;
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("messagepane");
  document.getElementById("messagetxt").innerHTML=str;
  base.style.top="0";
  pane.style.top="50%";
  pane.style.transform="translateY(-50%)";
}
function MessageClose(){
  if(messageCloseUrl)
    location.href=messageCloseUrl;
  else{
    const base=document.getElementById("dialogbase");
    const pane=document.getElementById("messagepane");
    base.style.top="100%";
    pane.style.top="100%";
    pane.style.transform="translateY(0%)";
  }
}
function AboutClose(){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("aboutpane");
  base.style.top="100%";
  pane.style.top="100%";
  pane.style.transform="translateY(0%)";
}
function AboutOpen(){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("aboutpane");
  base.style.top="0";
  pane.style.top="50%";
  pane.style.transform="translateY(-50%)";
}
function TageditCancel(){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("tageditpane");
  base.style.top="100%";
  pane.style.top="100%";
  pane.style.transform="translateY(0%)";
}
function TageditOk(){
  const sel=document.getElementById("tagedittype");
  const tags = sel.options[sel.selectedIndex].value + "," + document.getElementById("tagedittxt").value;
  const id = document.getElementById("infoid").innerHTML;
  fetch("./gallery.php", {
    method:"PUT",
    headers:{
      "Content-Type":"application/json"
    },
    body:JSON.stringify({
      "cmd":"tag",
      "tag":tags,
      "id":id,
    })
  }).then(res=>res.json())
  .then(res=>{
    if(res.result){
      for(let i=0;i<items.length;++i){
        if(items[i].item.id==id){
          items[i].item.tags=tags;
          document.getElementById("infotags").innerHTML=tags;
        }
      }
      MessageOpen("TagEdit success.");
    }
    else{
      MessageOpen("TagEdit fail.");
    }
  });
  TageditCancel();
}
function TageditOpen(){
  const id = document.getElementById("infoid").innerHTML;
  let i;
  for(i=items.length-1; i>=0; --i)
    if(items[i].item.id==id)
      break;
  if(i<0)
    return;
  const type=document.getElementById("tagedittype");
  const tags=items[i].item.tags.split(",");
  switch(tags[0]){
  case "$knob": type.selectedIndex=0; break;
  case "$slider": type.selectedIndex=1; break;
  case "$switch": type.selectedIndex=2; break;
  case "$other":type.selectedIndex=3; break;
  }
  document.getElementById("tagedittxt").value = tags.splice(1).join(",");
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("tageditpane");
  base.style.top="0";
  pane.style.top="50%";
  pane.style.transform="translateY(-50%)";
}
function InfoClose(){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("infopane");
  base.style.top="100%";
  pane.style.top="100%";
  pane.style.transform="translateY(0%)";
}
function InfoOpen(item){
  const base=document.getElementById("dialogbase");
  const pane=document.getElementById("infopane");
  infoknob=document.getElementById("infoknob");
  base.style.top="0";
  pane.style.top="50%";
  pane.style.transform="translateY(-50%)";
  infoknob.innerHTML=`<img src="${GetGalleryImgUrl(item.id)}"/>`;
  let licitem = item.file;
  if(item.author)
    licitem += ` by ${item.author}`;
  let cc="";
  switch(item.license){
  case "CC0" : cc="<a target='_blank' href='https://creativecommons.org/publicdomain/zero/1.0/'>Creative Commons CC0 Public Domain</a>"; break;
  case "CC-BY" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by/3.0/'>Creative Commons CC BY-NC 3.0</a>"; break;
  case "CC-BY-SA" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-sa/3.0/'>Creative Commons CC BY-SA 3.0</a>"; break;
  case "CC-BY-ND" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nd/3.0/'>Creative Commons CC BY-ND 3.0</a>"; break;
  case "CC-BY-NC" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nc/3.0/'>Creative Commons CC BY-NC 3.0</a>"; break;
  case "CC-BY-NC-SA" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nc-sa/3.0/'>Creative Commons CC BY-NC-SA 3.0</a>"; break;
  case "CC-BY-NC-ND" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nc-nd/3.0/'>Creative Commons CC BY-NC-ND 3.0</a>"; break;
  case "CC-BY-4.0" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by/4.0/'>Creative Commons CC BY-NC 4.0</a>"; break;
  case "CC-BY-SA-4.0" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-sa/4.0/'>Creative Commons CC BY-SA 4.0</a>"; break;
  case "CC-BY-ND-4.0" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nd/4.0/'>Creative Commons CC BY-ND 4.0</a>"; break;
  case "CC-BY-NC-4.0" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nc/4.0/'>Creative Commons CC BY-NC 4.0</a>"; break;
  case "CC-BY-NC-SA-4.0" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nc-sa/4.0/'>Creative Commons CC BY-NC-SA 4.0</a>"; break;
  case "CC-BY-NC-ND-4.0" : cc="<a target='_blank' href='https://creativecommons.org/licenses/by-nc-nd/4.0/'>Creative Commons CC BY-NC-ND 4.0</a>"; break;
  default: cc="Public Domain"; break;
  }
  if(item.license != "PD")
    licitem += " &copy;" + item.date.substring(0,4);
  let req = `${licitem}`;
  if(item.license == "PD" || item.license == "CC0")
    req = "None";
  let lic = `<img src="./img/${item.license}.png"/><br/><div style="font-size:10px">${licitem} is licensed under a ${cc}.</div>`;
  document.getElementById("infoid").innerHTML=item.id;
  document.getElementById("infofile").innerHTML=item.file;
  document.getElementById("infodate").innerHTML=item.date;
  document.getElementById("infosize").innerHTML=item.size;
  document.getElementById("infoauth").innerHTML=item.author;
  document.getElementById("infolic").innerHTML=lic;
  document.getElementById("infotags").innerHTML=item.tags;
  document.getElementById("infocom").innerHTML=item.comment;
  document.getElementById("infolink").innerHTML="https://www.g200kg.com/en/webknobman/gallery.php?m=p&p="+item.id;
  document.getElementById("infonotice").innerHTML=req;
  document.getElementById("infodelid").value = item.id;
}
function Download(){
  const id=document.getElementById("infoid").innerHTML;
  const fnam=encodeURIComponent(document.getElementById("infofile").innerHTML);
  location.href=`./gallery.php?m=get&n=${id}&t=bin&f=${fnam}`;
}
function OpenEasyRender(){
  const id=document.getElementById("infoid").innerHTML;
  const fnam=encodeURIComponent(document.getElementById("infofile").innerHTML);
  window.open(`./index.html?f=${fnam}&n=${id}&m=1`,"_blank");
}
function OpenWebKnobMan(){
  const id=document.getElementById("infoid").innerHTML;
  const fnam=encodeURIComponent(document.getElementById("infofile").innerHTML);
  window.open(`./index.html?f=${fnam}&n=${id}`, "_blank");
}
async function GetDB(){
  const res = await fetch('./gallery.php?m=list');
  const data = await res.json();
  return data;
}

let imgcnt=0;
function LoadImg(){
  const item=items[imgcnt];
  const elm=document.getElementById("itemimg"+item.item.id);
  elm.onload=()=>{
    if(imgcnt<items.length)
      LoadImg();
  };
  elm.src=GetGalleryImgUrl(item.item.id);
  ++imgcnt;
}

async function Init(){
  const db = await GetDB();
  const base=document.getElementById("base");
  const insertpoint=document.getElementById("insertpoint");
  for(let i=0;i<db.length;++i){
    const item=db[i];
    const div=document.createElement("div");
    div.classList.add("itempanel");
    div.id=item.id;
    div.addEventListener("click",()=>{InfoOpen(item)});
    let lic;
    lic=`./img/${item.license}.png`;
    let auth="";
    if(item.author)
      auth="by "+item.author;
    div.innerHTML=`<div class="itemdesc"><div class="itemid">#${item.id}</div><div class="itemdate"> ${item.date.substring(0,16)}</div><div class="itemfile">${item.file}</div><div class="itemauth">${auth}</div><div class="itemcom">* ${item.comment}</div><img class="itemlic" src="${lic}"/></div><div class="knobbase"><img class="knob"/></div>`;
    base.insertBefore(div,insertpoint);
    items.push({div:div,item:item});
    lazyitems.push({img:div.children[1].children[0], url:GetGalleryImgUrl(item.id)});
  }
  InfoClose();
  const param=location.search;
  if(param.indexOf("?r=")==0){
    MessageOpen(atob(param.substring(3)),"./gallery.php");
  }
  if(param.indexOf("?m=p")==0){
    const p=param.split("&p=")[1];
    let i;
    for(i=0;i<items.length;++i)
      if(items[i].item.id==p)
        break;
    if(i<items.length)
      InfoOpen(items[i].item);
  }
  lazyload();
}
window.onload=Init;
</script>

<style>
html{
  margin:0;
  padding:0;
  height:100%;
  margin:0;
  padding:0;
}
body{
  font-family: 'Lato', sans-serif;
  margin:0;
  padding:0;
  height:100%;
  margin:0;
  padding:0;
}
#container{
  background:#224;
  position:relative;
  margin:0;
  padding:0;
}
#header{
  background:#fff;
  background-image:url("./img/g200kgcorner.png");
  background-repeat:no-repeat;
  position:fixed;
  height:140px;
  width:100%;
  top:0;
  box-shadow:0px 10px 10px rgba(0,0,0,0.3);
  display:flex;
  margin:0;
  padding:0;
}
h1{
  color:#334477;
  margin:0px 20px;
  font-family: 'Cinzel Decorative', cursive;
  font-size:42px;
}
#ad{
  position:absolute;
  left:10px;
  top:80px;
  background:#aae;
  width:468px;
  height:60px;
}
#base{
  display:flex;
  flex-wrap: wrap;
  background:#88a;
  margin:140px 20px 20px 20px;
  padding:10px 0px 0px 0px;
  justify-content: center;
}
.itempanel{
  width:250px;
  height:250px;
  overflow:hidden;
  background:#445566;
  color:#fff;
  border:1px solid #224;
  margin:3px;
  position:relative;
  box-sizing:border-box;
  cursor:pointer;
}
.itemdesc{
  position:relative;
  height:82px;
  background:#345;
  border-bottom:1px solid #000;
  box-sizing:border-box;
}
.itemid{
  position:absolute;
  font-size:16px;
  font-weight:bold;
  left:2px;
  top:4px;
}
.itemdate{
  position:absolute;
  font-size:11px;
  font-weight:normal;
  top:9px;
  left:65px;
}
.itemlic{
  position:absolute;
  top:0px;
  right:0px;
}
.itemfile{
  position:absolute;
  font-size:13px;
  top:31px;
  left:0;
  height:21px;
  width:500px;
  padding:3px 0px 0px 5px;
  overflow:hidden;
  background:#234;
  box-sizing:border-box;
  color:#ffc;
  vertical-align:middle;
}
.itemauth{
  position:absolute;
  font-size:11px;
  top:52px;
  left:15px;
  height:15px;
  overflow:hidden;
  vertical-align:middle;
}
.itemcom{
  position:absolute;
  font-size:11px;
  top:67px;
  left:5px;
  height:15px;
  overflow:hidden;
  color:#ccc;
  vertical-align:middle;
}
.emptyitem{
  width:250px;
  height:250px;
  position:relative;
  margin:3px;
  box-sizing:border-box;
}
#base .knobbase{
  position:relative;
  height:168px;
}
#base div .knob{
  display:block;
  box-sizing:border-box;
  max-width:240px;
  max-height:158px;
  object-fit:contain;
  position:absolute;
  margin:auto;
  top:0;
  bottom:0;
  left:0;
  right:0;
}
#optsearch{
  position:absolute;
  top:10px;
  left:620px;
  width:500px;
  height:25p;x
}
#optsearch input{
  width:130px;
}
#optbgcolor{
  position:absolute;
  top:35px;
  left:620px;
  width:500px;
}
#optupload{
  position:absolute;
  top:66px;
  height:25px;
  width:250px;
  left:620px;
}
#optabout{
  position:absolute;
  width:250px;
  height:25px;
  top:95px;
  left:620px;
}
#optad{
  position:absolute;
  left:280px;
  top:70px;
  width:320px;
  height:50px;
  background:#eef;
}
#dialogbase{
  position:fixed;
  top:100%;
  left:0;
  width:100%;
  height:100%;
  background:rgba(0,0,0,0.3);
}
.dialogtitle{
  background:#8888dd;
  width:100%;
  height:35px;
  line-height:35px;
  font-size:16px;
  font-weight:bold;
  vertical-align:middle;
  padding-left:20px;
  box-sizing:border-box;
  color:#eeeeff;
}
#aboutpane{
  position:fixed;
  width:800px;
  height:380px;
  background:#cce;
  top:100%;
  left:0;
  right:0;
  margin:auto;
  padding:0;
  transform: translateY(0%);
  border:1px solid #223;
  box-shadow:10px 10px 10px rgba(0,0,0,0.5);
  transition-duration:0.5s;
}
#aboutpane button{
  width:220px;
  height:24px;
}
#confirmpane{
  position:fixed;
  width:600px;
  height:280px;
  background:#cce;
  top:100%;
  left:0;
  right:0;
  margin:auto;
  padding:0;
  transform: translateY(0%);
  border:1px solid #223;
  box-shadow:10px 10px 10px rgba(0,0,0,0.5);
  transition-duration:0.5s;
}
#tageditpane{
  position:fixed;
  width:420px;
  height:100px;
  background:#cce;
  top:100%;
  left:0;
  right:0;
  margin:auto;
  padding:0;
  transform: translateY(0%);
  border:1px solid #223;
  box-shadow:10px 10px 10px rgba(0,0,0,0.5);
  transition-duration:0.5s;
}
#tagedit{
  width:150px;
}
#messagepane{
  position:fixed;
  width:600px;
  height:280px;
  background:#cce;
  top:100%;
  left:0;
  right:0;
  margin:auto;
  padding:0;
  transform: translateY(0%);
  border:1px solid #223;
  box-shadow:10px 10px 10px rgba(0,0,0,0.5);
  transition-duration:0.5s;
}
#messagetxt{
  margin:20px;
}
#messageokbtn{
  margin:20px;
}
#uploadpane{
  position:fixed;
  width:800px;
  height:380px;
  background:#cce;
  top:100%;
  left:0;
  right:0;
  margin:auto;
  padding:0;
  transform: translateY(0%);
  border:1px solid #223;
  box-shadow:10px 10px 10px rgba(0,0,0,0.5);
  transition-duration:0.5s;
}
#uploadpane table{
  position:absolute;
  top:50px;
  left:20px;
  width:720px;
  padding:0;
}
#uploadpane select{
  width:300px;
  height:24px;
}
#uploadpane option{
  width:200px;
  height:28px;
}
#uploadlictxt{
  font-size:11px;
}
#uploadexecbtn{
  position:absolute;
  left:20px;
  top:300px;
  width:150px;
  height:24px;
}
#uploadcancelbtn{
  position:absolute;
  left:200px;
  top:300px;
  width:150px;
  height:24px;
}
#infopane{
  position:fixed;
  width:800px;
  height:440px;
  background:#cce;
  top:100%;
  left:0;
  right:0;
  margin:auto;
  padding:0;
  transform: translateY(0%);
  border:1px solid #223;
  box-shadow:10px 10px 10px rgba(0,0,0,0.5);
  transition-duration:0.5s;
}
#infoknob{
  width:256px;
  height:256px;
  border:1px solid #000;
  background:#445566;
  margin:20px;
  position:relative;
}
#infoknob img{
  position:absolute;
  top:0;
  bottom:0;
  left:0;
  right:0;
  margin:auto;
  max-width:250px;
  max-height:250px;
  object-fit:contain;
}
#infodetail{
}
#infodetail table{
  position:absolute;
  top:50px;
  left:300px;
  width:480px;
  padding:0;
  font-size:14px;
}
#infonoticelabel{
  padding:5px 10px;
  font-size:11px;
}
#infonotice{
  display:inline-block;
  padding:5px 10px;
  font-size:11px;
  width:200px;
  height:18px;
}
#infolink{
  font-size:11px;
}
#infopane button{
  margin:0px;
  width:180px;
  height:24px;
}
#infodownloadbtn{
  position:absolute;
  top:330px;
  left:20px;
}
#infoeasyrenderbtn{
  position:absolute;
  top:355px;
  left:20px;
}
#infowebknobmanbtn{
  position:absolute;
  top:380px;
  left:20px;
}
#infodelkey{
  width:80px;
}
#infodelbtn{
  width:200px;
}
#infoclosebtn{
  position:absolute;
  top:400px;
  left:520px;
}
#aboutclosebtn{
  position:absolute;
  top:330px;
  left:520px;
}
table{
  border:1px solid #88c;
}
table tr {
}
table th {
  width:100px;
  font-weight:normal;
  border:1px solid #88c;
}
table td {
  word-break:break-all;
  padding:0px 5px;
  border:1px solid #88c;
  background:#cce;
}

</style>
</head>
<body>
<div id="container">
  <div id="base">
    <div class="emptyitem" id="insertpoint"></div>
    <div class="emptyitem"></div>
    <div class="emptyitem"></div>
    <div class="emptyitem"></div>
    <div class="emptyitem"></div>
  </div>
  <div id="header">
    <a href="https://www.g200kg.com/" target="_blank"><img src="https://www.g200kg.com/image/logo/g200kg160x80.png"/></a>
    <div><a href="./index.html" target="_blank"><img style="width:80px;height:80px;margin:10px 10px 0px 10px" src="./img/JKnobMan.png"/><br/><div>WebKnobMan</div></a></div>
    <div style="display:inline">
      <h1>KnobGallery</h1>
    </div>
    <div id="optad">
    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
      <!-- WEBKNOBMAN -->
      <ins class="adsbygoogle"
          style="display:block"
          data-ad-client="ca-pub-5618328583582553"
          data-ad-slot="4865244564"
          data-ad-format="auto"
          data-full-width-responsive="true"></ins>
      <script>
      (adsbygoogle = window.adsbygoogle || []).push({});
      </script>      
    </div>
    <div id="optsearch">Free Word Search : <select id="searchtype"><option value="">All</option><option value="$knob">Knob</option><option value="$slider">Slider</option><option value="$switch">Switch</option><option value="$other">Other</option></select> <input id="search" onchange="Search()"/> <button onclick="Search()">Search</button></div>
    <div id="optbgcolor">Background Color : <input type="color" id="bgcolor" value="#445566" list="bgcollist" onchange="BgColor()"/>
      <datalist id="bgcollist">
        <option>#000000</option>
        <option>#332233</option>
        <option>#445566</option>
        <option>#227722</option>
        <option>#888888</option>
        <option>#cc7744</option>
        <option>#dd6677</option>
        <option>#ccaa88</option>
        <option>#cccccc</option>
        <option>#ffffff</option>
      </datalist></div>
    <button id="optupload" onclick="UploadOpen()">Upload and Share Your knob-file</button>
    <button id="optabout" onclick="AboutOpen()">About KnobGallery</button>
  </div>
  <div id="dialogbase">
    <div id="infopane">
      <div class="dialogtitle">Knob Info</div>
      <div id="infoknob"></div>
      <div id="infodetail">
        <table>
        <tr><th>ID#</th><td id="infoid"></th></tr>
        <tr><th>File</th><td id="infofile"></td></tr>
        <tr><th>Date</th><td id="infodate"></td></tr>
        <tr><th>Size</th><td id="infosize"></td></tr>
        <tr><th>Author</th><td id="infoauth"></td></tr>
        <tr><th>License</th><td id="infolic"></td></tr>
        <tr><th>Tags</th><td><span id="infotags"></span><button id="infotageditbtn" onclick="TageditOpen()" style="width:50px;float:right">Edit</button></td></tr>
        <tr><th>Comment</th><td id="infocom"></td></tr>
        <tr><th>LinkToThisKnob</th><td id="infolink"></td></tr>
        <tr><td colspan="2" style="font-size:12px"> Required credit notation when use : <span id="infonotice"></span></td></tr>
        <tr><td colspan="2" style="font-size:12px"><form action="./gallery.php" method="post"><input type="hidden" name="m" value="de"/><input id="infodelid" type="hidden" name="n" value=""/><input type="submit" id="infodelbtn" value="Delete this Item"/></button> delKey:<input id="infodelkey" name="dk"/></form></td></tr>
        </table>
      </div>
      <button id="infodownloadbtn" onclick="Download()">Download '.knob' file</button><br/>
      <button id="infoeasyrenderbtn" onclick="OpenEasyRender()">Open Easy Rendering</button><br/>
      <button id="infowebknobmanbtn" onclick="OpenWebKnobMan()">Open with WebKnobMan</button>
      <button id="infoclosebtn" onclick="InfoClose()">Close</button>
    </div>
    <div id="aboutpane">
      <div class="dialogtitle">About KnobGallery</div>
      <ul>
        <li><b>Knob Gallery</b> is a free sharing space for ".knob" files created with KnobMan or WebKnobMan. </li>
        <li>Anyone can upload ".knob" files to this gallery with no restrictions. </li>
        <li>Anyone can use .knob in this gallery in accordance with the license of each item.</li>
      </ul>

      <ul>
        <li>KnobGallery works in conjunction with WebKnobMan. KnobGallery allows you to perform the following actions on the selected knob.
        <ul>
          <li>Download .knob-file</li>
          <li>Open with EasyRendering (just get the PNG image-strip)</li>
          <li>Open with WebKnobMan (to detail editting)</li>
        </ul>
        <li>The .knob-file can be opened with the standalone JKobMan application.</li>
        <li>EasyRendering is WebKnobMan's rendering function, which allows you to download an image (png) by specifying the knob size and the number of frames.</li>
        <li>'Open with WebKnobMan' opens .knob-data in the WebKnobMan web app. WebKnobMan is an almost full featured KnobMan compatible web application.</li>
      </ul>
      <button id="aboutclosebtn" onClick="AboutClose()">Close</button>
    </div>
    <div id="uploadpane">
      <div class="dialogtitle">Upload knob-file</div>
      <form action="./gallery.php" method="post" enctype="multipart/form-data">
      <table>
        <tr><th>File</th><td><input type="file" name="file"/></td></tr>
        <tr><th>Author</th><td><input name="a" /></td></tr>
        <tr><th>License</th><td style="height:50px;vertical-align:middle">
          <select name="l" onchange="UploadLicChange(this)">
            <option id="upload-CC0" value="CC0" selected>CC0 (PublicDomain)</option>
            <option id="upload-CC-BY" value="CC-BY-4.0">CC BY 4.0 (Credit)</option>
            <option id="upload-CC-BY-SA" value="CC-BY-SA-4.0">CC BY-SA 4.0 (Credit, ShareAlike)</option>
            <option id="upload-CC-BY-ND" value="CC-BY-ND-4.0">CC BY-ND 4.0 (Credit, No Derivatives)</option>
            <option id="upload-CC-BY-NC" value="CC-BY-NC-4.0">CC BY-NC 4.0 (Credit, NonCommercial)</option>
            <option id="upload-CC-BY-NC-SA" value="CC-BY-NC-SA-4.0">CC BY-NC-SA 4.0 (Credit, NonCommercial, ShareAlike)</option>
            <option id="upload-CC-BY-NC-ND" value="CC-BY-NC-ND-4.0">CC BY-NC-ND 4.0 (Credit, NonCommercial, NoDerivatives)</option>
          </select>
          <a id="uploadliclink" href="https://creativecommons.org/publicdomain/zero/1.0/" target="_blank"><img id="uploadlicimg" style="vertical-align:middle" src="./img/CC0.png"/> <span id="uploadlictxt">Creative Commons CC0 1.0</span></a>
        </td></tr>
        <tr><th>Type</th><td><select name="p"><option value="$knob," selected>Knob</option><option value="$slider,">Slider</option><option value="$switch">Switch</option><option value="$other,">Other</option></select></td></tr>
        <tr><th>Tags</th><td><input name="t"/><span style="font-size:11px"> (Comma separated words)</span></td></tr>
        <tr><th>DelKey</th><td><input name="k"/><span style="font-size:11px"> (Password, required when delete this item)</span></td></tr>
        <tr><th>Comment</th><td><input name="c"/></td></tr>
      </table>
      <input type="hidden" name="m" value="up"/>
      <input type="submit" id="uploadexecbtn" value="Upload Exec"/>
      </form>
      <button id="uploadcancelbtn" onclick="UploadCancel()">Cancel</button>
    </div>
    <div id="messagepane">
      <div class="dialogtitle">Knob Gallery</div>
      <div id="messagetxt"></div>
      <button id="messageokbtn" onclick="MessageClose()">Ok</button>
    </div>
    <div id="confirmpane">
      <div class="dialogtitle">Knob Gallery</div>
      <div id="confirmtxt"></div>
      <button id="confirmokbtn" onclick="ConfirmOk()">Ok</button>
      <button id="confirmcancelbtn" onclick="ConfirmCancel()">Cancel</button>
    </div>
    <div id="tageditpane">
      <div class="dialogtitle">Tag Edit</div>
      <div style="position:absolute;top:50px;left:30px">Tags : <select id="tagedittype"><option value="$knob">Knob</option><option value="$slider">Slider</option><option value="$switch">Switch</option><option value="$other">Other</option></select> <input id="tagedittxt"/>
      <button id="tageditok" onclick="TageditOk()">Ok</button> <button id="tageditcancel" onclick="TageditCancel()">Cancel</button>
      </div>
    </div>
  </div>
</div>
</body>