// Hi/Lo bar chart

/* ── CHART ── */
function drawChart(daily,days) {
  const canvas=document.getElementById('chart');
  const W=canvas.parentElement.getBoundingClientRect().width||940;
  const H=120;
  canvas.width=W; canvas.height=H;
  const ctx=canvas.getContext('2d');
  ctx.clearRect(0,0,W,H);
  if(!daily||days<2) return;

  const highs=[],lows=[],precips=[];
  for(let i=0;i<days;i++){
    highs.push(daily.temperature_2m_max[i]??null);
    lows.push(daily.temperature_2m_min[i]??null);
    precips.push(daily.precipitation_sum[i]??0);
  }

  const PT=18,PB=8,colW=W/days,cH=H-PT-PB;
  const allT=[...highs,...lows].filter(v=>v!=null);
  if(!allT.length) return;
  const tMin=Math.floor(Math.min(...allT)-2),tMax=Math.ceil(Math.max(...allT)+2),tRng=tMax-tMin||1;
  const pMax=Math.max(...precips,2),barMaxH=cH*0.28;
  const xPos=i=>colW*i+colW/2;
  const yTemp=v=>PT+(1-(v-tMin)/tRng)*cH;
  const fs=Math.max(8,W*0.013);
  const F=`-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif`;

  const step=tRng<=10?2:5;
  ctx.lineWidth=1;
  for(let t=Math.ceil(tMin/step)*step;t<=tMax;t+=step){ctx.strokeStyle='#f0f0f0';ctx.beginPath();ctx.moveTo(0,yTemp(t));ctx.lineTo(W,yTemp(t));ctx.stroke();}
  if(tMin<=0&&tMax>=0){ctx.strokeStyle='#ddd';ctx.setLineDash([3,3]);ctx.beginPath();ctx.moveTo(0,yTemp(0));ctx.lineTo(W,yTemp(0));ctx.stroke();ctx.setLineDash([]);}
  ctx.strokeStyle='#eee';
  for(let i=1;i<days;i++){ctx.beginPath();ctx.moveTo(colW*i,0);ctx.lineTo(colW*i,H);ctx.stroke();}

  const barW=Math.max(6,colW*0.26);
  for(let i=0;i<days;i++){
    const bH=(precips[i]/pMax)*barMaxH;
    if(bH>0.5){
      const x=xPos(i);ctx.fillStyle='#d8d8d8';ctx.fillRect(x-barW/2,H-PB-bH,barW,bH);
      if(precips[i]>=0.5){ctx.fillStyle='#aaa';ctx.font=`${Math.max(7,fs*0.76)}px ${F}`;ctx.textAlign='center';ctx.fillText(precips[i].toFixed(1),x,H-PB-bH-3);}
    }
  }
  ctx.fillStyle='#ccc';ctx.textAlign='right';ctx.font=`${Math.max(7,fs*0.76)}px ${F}`;ctx.fillText(pMax.toFixed(0)+'mm',W-3,H-PB-barMaxH+8);

  ctx.beginPath();let mv=false;
  for(let i=0;i<days;i++){if(highs[i]==null)continue;mv?ctx.lineTo(xPos(i),yTemp(highs[i])):ctx.moveTo(xPos(i),yTemp(highs[i]));mv=true;}
  for(let i=days-1;i>=0;i--){if(lows[i]==null)continue;ctx.lineTo(xPos(i),yTemp(lows[i]));}
  ctx.closePath();ctx.fillStyle='rgba(0,0,0,0.05)';ctx.fill();

  ctx.beginPath();ctx.strokeStyle='#000';ctx.lineWidth=2;let s=false;
  for(let i=0;i<days;i++){if(highs[i]==null)continue;if(!s){ctx.moveTo(xPos(i),yTemp(highs[i]));s=true;}else ctx.lineTo(xPos(i),yTemp(highs[i]));}
  ctx.stroke();

  ctx.beginPath();ctx.strokeStyle='#999';ctx.lineWidth=1.5;ctx.setLineDash([4,3]);s=false;
  for(let i=0;i<days;i++){if(lows[i]==null)continue;if(!s){ctx.moveTo(xPos(i),yTemp(lows[i]));s=true;}else ctx.lineTo(xPos(i),yTemp(lows[i]));}
  ctx.stroke();ctx.setLineDash([]);

  for(let i=0;i<days;i++){
    const x=xPos(i);
    if(highs[i]!=null){const y=yTemp(highs[i]);ctx.fillStyle='#000';ctx.beginPath();ctx.arc(x,y,3,0,Math.PI*2);ctx.fill();ctx.font=`700 ${fs}px ${F}`;ctx.textAlign='center';ctx.fillText(Math.round(highs[i])+'°',x,y-5);}
    if(lows[i]!=null){const y=yTemp(lows[i]);ctx.fillStyle='#999';ctx.beginPath();ctx.arc(x,y,2.5,0,Math.PI*2);ctx.fill();ctx.font=`${fs}px ${F}`;ctx.textAlign='center';ctx.fillText(Math.round(lows[i])+'°',x,y+11);}
  }

  ctx.textAlign='left';ctx.fillStyle='#ccc';ctx.font=`${Math.max(7,fs*0.82)}px ${F}`;
  for(let t=Math.ceil(tMin/step)*step;t<=tMax;t+=step)ctx.fillText(t+'°',3,yTemp(t)+3);
  ctx.font=`${Math.max(7,fs*0.85)}px ${F}`;
  ctx.fillStyle='#000';ctx.fillText('— Hi',4,13);
  ctx.fillStyle='#999';ctx.fillText('- Lo',38,13);
  ctx.fillStyle='#ccc';ctx.fillText('▮ Rain',70,13);
}

