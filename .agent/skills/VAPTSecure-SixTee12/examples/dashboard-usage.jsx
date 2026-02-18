import { useState, useEffect, useRef } from "react";

const RISKS = [
  { id:"RISK-001", title:"wp-cron.php Leads to DoS Attack", category:"Configuration", severity:"high", cvss:7.5, owasp:"A06:2025", cwe:"CWE-400", summary:"wp-cron.php allows resource exhaustion via repeated spam requests.", attack:"Attacker spams /wp-cron.php to exhaust CPU/memory — server DoS.", payloads:[{method:"GET",url:"/wp-cron.php",expectedStatus:403,automated:true}], fix:"define('DISABLE_WP_CRON', true) in wp-config.php; use server cron.", priority:8 },
  { id:"RISK-002", title:"XML-RPC Pingback DDoS Amplification", category:"Infosec", severity:"high", cvss:7.5, owasp:"A02:2025", cwe:"CWE-918", summary:"XML-RPC enabled allows distributed pingback amplification attacks.", attack:"Attacker sends pingback.ping to /xmlrpc.php to DDoS third-party targets.", payloads:[{method:"POST",url:"/xmlrpc.php",expectedStatus:403,automated:true}], fix:"Block /xmlrpc.php via .htaccess deny; disable XML-RPC entirely.", priority:9 },
  { id:"RISK-003", title:"Username Enumeration via REST API", category:"Authentication", severity:"medium", cvss:5.3, owasp:"A01:2025", cwe:"CWE-204", summary:"/wp-json/wp/v2/users exposes usernames without authentication.", attack:"GET /wp-json/wp/v2/users harvests all usernames for credential attacks.", payloads:[{method:"GET",url:"/wp-json/wp/v2/users",expectedStatus:401,automated:true}], fix:"Restrict REST users endpoint; require auth via add_filter().", priority:6 },
  { id:"RISK-004", title:"Email Flooding via Password Reset", category:"Authentication", severity:"medium", cvss:5.3, owasp:"A07:2025", cwe:"CWE-770", summary:"Unlimited password reset emails via wp-login.php?action=lostpassword.", attack:"Bot floods victim inbox with password reset emails.", payloads:[{method:"POST",url:"/wp-login.php?action=lostpassword",expectedStatus:429,automated:true,rateLimit:true}], fix:"Rate limit: max 3 password resets per 5 min per IP.", priority:5 },
  { id:"RISK-005", title:"Admin Username via Author Query", category:"Infosec", severity:"medium", cvss:5.3, owasp:"A01:2025", cwe:"CWE-204", summary:"/?author=1 reveals admin usernames via IDOR redirect.", attack:"Attacker iterates /?author=N to enumerate all usernames.", payloads:[{method:"GET",url:"/?author=1",expectedStatus:403,automated:true},{method:"GET",url:"/?author=2",expectedStatus:403,automated:true}], fix:"Block author query redirects via rewrite rules; return 403.", priority:6 },
  { id:"RISK-006", title:"Endpoint Disclosure via WP REST Routes", category:"API Security", severity:"low", cvss:3.7, owasp:"A02:2025", cwe:"CWE-200", summary:"Auto-generated REST routes expose internal API surface.", attack:"GET /wp-json/ maps entire internal API for targeted exploitation.", payloads:[{method:"GET",url:"/wp-json/",expectedStatus:401,automated:true}], fix:"Require auth for /wp-json/ index; restrict namespace discovery.", priority:3 },
  { id:"RISK-007", title:"No Rate Limiting on WordPress Login", category:"Authentication", severity:"high", cvss:7.5, owasp:"A07:2025", cwe:"CWE-307", summary:"wp-login.php allows unlimited brute-force login attempts.", attack:"Credential stuffing / brute-force with no lockout.", payloads:[{method:"POST",url:"/wp-login.php",automated:false,manual:"Configure fail2ban + Nginx limit_req; lockout after 5 attempts."}], fix:"Deploy fail2ban; lockout IP after 5 failed attempts in 10 min.", priority:9 },
  { id:"RISK-008", title:"Username Enumeration via Login Errors", category:"Authentication", severity:"medium", cvss:5.3, owasp:"A07:2025", cwe:"CWE-203", summary:"wp-login.php error messages differentiate valid vs invalid users.", attack:"Differential error messages reveal which usernames exist.", payloads:[{method:"POST",url:"/wp-login.php",automated:true,loginTest:true,forbidden:["unknown username","incorrect password"]}], fix:"Override all login errors to return generic 'Invalid credentials'.", priority:5 },
  { id:"RISK-009", title:"No Rate Limiting on Contact Forms", category:"Configuration", severity:"medium", cvss:5.3, owasp:"A06:2025", cwe:"CWE-770", summary:"Contact/registration forms allow spam without submission limits.", attack:"Bots flood forms causing spam and resource exhaustion.", payloads:[{method:"POST",url:"/contact/",automated:true,rateLimit:true,attempts:10}], fix:"Add rate limiting + reCAPTCHA v3 to all public forms.", priority:4 },
  { id:"RISK-010", title:"Server Banner Grabbing via HTTP Headers", category:"Infosec", severity:"low", cvss:3.7, owasp:"A02:2025", cwe:"CWE-200", summary:"Server/X-Powered-By headers expose version info for exploit targeting.", attack:"Attacker reads version headers to match known CVEs.", payloads:[{method:"GET",url:"/",automated:true,headerCheck:true,forbidden:["Server","X-Powered-By"]}], fix:"ServerTokens Prod (Apache) or server_tokens off (Nginx).", priority:3 },
  { id:"RISK-011", title:"WordPress Version via readme.html", category:"Infosec", severity:"medium", cvss:5.3, owasp:"A02:2025", cwe:"CWE-200", summary:"/readme.html exposes exact WordPress version to attackers.", attack:"Attacker reads readme.html to identify WP version and CVEs.", payloads:[{method:"GET",url:"/readme.html",expectedStatus:403,automated:true}], fix:"Block /readme.html via .htaccess deny; delete the file.", priority:4 },
  { id:"RISK-012", title:"HSTS Header Not Implemented", category:"Configuration", severity:"medium", cvss:5.3, owasp:"A02:2025", cwe:"CWE-319", summary:"Missing Strict-Transport-Security enables MITM/HTTPS downgrade.", attack:"SSL stripping attack when user visits HTTP version of site.", payloads:[{method:"GET",url:"/",automated:true,headerRequired:"Strict-Transport-Security"}], fix:"Add 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload'.", priority:6 },
];

const TOTAL_MAX = RISKS.reduce((s,r)=>s+r.cvss,0);

const SEV = {
  high:  { label:"HIGH",   color:"#FF4444", glow:"rgba(255,68,68,0.35)",  bg:"rgba(255,68,68,0.12)",  dot:"#FF4444" },
  medium:{ label:"MEDIUM", color:"#FFAA00", glow:"rgba(255,170,0,0.3)",   bg:"rgba(255,170,0,0.1)",   dot:"#FFAA00" },
  low:   { label:"LOW",    color:"#00C896", glow:"rgba(0,200,150,0.25)",  bg:"rgba(0,200,150,0.1)",   dot:"#00C896" },
};

function ScoreRing({ score }) {
  const r = 44, circ = 2 * Math.PI * r;
  const dash = (score / 100) * circ;
  const color = score >= 80 ? "#00C896" : score >= 50 ? "#FFAA00" : "#FF4444";
  return (
    <svg width="110" height="110" style={{ transform:"rotate(-90deg)" }}>
      <circle cx="55" cy="55" r={r} fill="none" stroke="rgba(255,255,255,0.07)" strokeWidth="8"/>
      <circle cx="55" cy="55" r={r} fill="none" stroke={color} strokeWidth="8"
        strokeDasharray={`${dash} ${circ}`} strokeLinecap="round"
        style={{ transition:"stroke-dasharray 1.2s cubic-bezier(.4,0,.2,1)", filter:`drop-shadow(0 0 8px ${color})` }}/>
      <text x="55" y="55" textAnchor="middle" dominantBaseline="central"
        fill={color} fontSize="22" fontWeight="700" fontFamily="'Google Sans', sans-serif"
        style={{ transform:"rotate(90deg)", transformOrigin:"55px 55px" }}>{score}</text>
      <text x="55" y="70" textAnchor="middle" dominantBaseline="central"
        fill="rgba(255,255,255,0.4)" fontSize="9" fontFamily="'Google Sans', sans-serif"
        style={{ transform:"rotate(90deg)", transformOrigin:"55px 55px" }}>/ 100</text>
    </svg>
  );
}

function CVSSArc({ score }) {
  const pct = score / 10;
  const r = 18, circ = Math.PI * r;
  const dash = pct * circ;
  const color = score >= 7 ? "#FF4444" : score >= 4 ? "#FFAA00" : "#00C896";
  return (
    <svg width="46" height="28" viewBox="0 0 46 28">
      <path d="M4,24 A19,19 0 0,1 42,24" fill="none" stroke="rgba(255,255,255,0.08)" strokeWidth="4" strokeLinecap="round"/>
      <path d="M4,24 A19,19 0 0,1 42,24" fill="none" stroke={color} strokeWidth="4" strokeLinecap="round"
        strokeDasharray={`${dash} ${circ}`}
        style={{ filter:`drop-shadow(0 0 4px ${color})` }}/>
      <text x="23" y="24" textAnchor="middle" fill={color} fontSize="9" fontWeight="700"
        fontFamily="'Roboto Mono', monospace">{score}</text>
    </svg>
  );
}

function TestResultBadge({ status }) {
  const cfg = {
    pass: { label:"✓ PROTECTED", color:"#00C896", bg:"rgba(0,200,150,0.12)" },
    fail: { label:"✗ VULNERABLE", color:"#FF4444", bg:"rgba(255,68,68,0.12)" },
    running: { label:"◌ SCANNING", color:"#4285F4", bg:"rgba(66,133,244,0.12)" },
    idle: { label:"— PENDING", color:"rgba(255,255,255,0.3)", bg:"rgba(255,255,255,0.04)" },
  }[status] || { label:"—", color:"#fff", bg:"transparent" };
  return (
    <span style={{ fontSize:10, fontFamily:"'Roboto Mono',monospace", fontWeight:700,
      color:cfg.color, background:cfg.bg, padding:"3px 8px", borderRadius:4,
      border:`1px solid ${cfg.color}33`, letterSpacing:"0.08em",
      animation: status==="running" ? "pulse 1.2s infinite" : "none" }}>
      {cfg.label}
    </span>
  );
}

function RiskCard({ risk, status, onRunTest, index }) {
  const [open, setOpen] = useState(false);
  const sev = SEV[risk.severity];
  const isHigh = risk.severity === "high";
  const isVuln = status === "fail";
  return (
    <div style={{
      background: "linear-gradient(145deg, #161E2E 0%, #111827 100%)",
      border: `1px solid ${isHigh && isVuln ? sev.color+"55" : "rgba(255,255,255,0.06)"}`,
      borderLeft: `3px solid ${sev.color}`,
      borderRadius: 12,
      padding:"0",
      overflow:"hidden",
      boxShadow: isVuln && isHigh
        ? `0 4px 24px ${sev.glow}, -3px 0 20px ${sev.glow}`
        : "0 4px 20px rgba(0,0,0,0.4)",
      animation: `slideUp 0.5s ease ${index * 0.05}s both`,
      transition:"box-shadow 0.3s, border-color 0.3s",
    }}>
      {/* Card Header */}
      <div style={{ padding:"16px 18px 12px", cursor:"pointer" }} onClick={() => setOpen(o=>!o)}>
        <div style={{ display:"flex", justifyContent:"space-between", alignItems:"flex-start", gap:8 }}>
          <div style={{ display:"flex", gap:8, alignItems:"center", flexWrap:"wrap" }}>
            <span style={{ fontFamily:"'Roboto Mono',monospace", fontSize:10, color:"#4285F4",
              background:"rgba(66,133,244,0.1)", padding:"2px 7px", borderRadius:4,
              border:"1px solid rgba(66,133,244,0.2)", letterSpacing:"0.05em" }}>
              {risk.id}
            </span>
            <span style={{ display:"flex", alignItems:"center", gap:4, fontSize:10, fontWeight:700,
              color:sev.color, background:sev.bg, padding:"2px 8px", borderRadius:4,
              letterSpacing:"0.08em" }}>
              <span style={{ width:6, height:6, borderRadius:"50%", background:sev.color,
                boxShadow:`0 0 6px ${sev.color}`,
                animation: isVuln ? "pulseGlow 1.5s infinite" : "none" }}/>
              {sev.label}
            </span>
            <TestResultBadge status={status} />
          </div>
          <div style={{ display:"flex", alignItems:"center", gap:8, flexShrink:0 }}>
            <CVSSArc score={risk.cvss} />
            <span style={{ color:"rgba(255,255,255,0.25)", fontSize:14, transition:"transform 0.3s",
              transform: open ? "rotate(180deg)" : "none" }}>▾</span>
          </div>
        </div>
        <h3 style={{ margin:"10px 0 6px", fontSize:13.5, fontWeight:600, lineHeight:1.4,
          color:"#E8EAED", fontFamily:"'Google Sans',sans-serif", letterSpacing:"-0.01em" }}>
          {risk.title}
        </h3>
        <div style={{ display:"flex", gap:6, flexWrap:"wrap" }}>
          <Tag label={risk.owasp} color="#4285F4" />
          <Tag label={risk.cwe} color="#9AA0A6" />
          <Tag label={risk.category} color="#81C995" />
        </div>
      </div>

      {/* Expanded Detail Drawer */}
      <div style={{ maxHeight: open ? 600 : 0, overflow:"hidden",
        transition:"max-height 0.4s cubic-bezier(.4,0,.2,1)" }}>
        <div style={{ borderTop:"1px solid rgba(255,255,255,0.06)", padding:"14px 18px 16px" }}>
          <p style={{ fontSize:12, color:"rgba(255,255,255,0.55)", margin:"0 0 10px",
            lineHeight:1.6, fontFamily:"'Google Sans Text',sans-serif" }}>
            {risk.summary}
          </p>
          <div style={{ background:"rgba(255,68,68,0.05)", border:"1px solid rgba(255,68,68,0.12)",
            borderRadius:8, padding:"10px 12px", marginBottom:12 }}>
            <div style={{ fontSize:10, color:"#FF4444", fontWeight:700, letterSpacing:"0.1em",
              marginBottom:4, fontFamily:"'Roboto Mono',monospace" }}>⚠ ATTACK SCENARIO</div>
            <p style={{ fontSize:11.5, color:"rgba(255,255,255,0.6)", margin:0, lineHeight:1.5 }}>
              {risk.attack}
            </p>
          </div>
          <div style={{ marginBottom:12 }}>
            <div style={{ fontSize:10, color:"rgba(255,255,255,0.3)", letterSpacing:"0.1em",
              fontFamily:"'Roboto Mono',monospace", marginBottom:6 }}>TEST PAYLOADS</div>
            {risk.payloads.map((p,i) => (
              <div key={i} style={{ background:"#0A0F1E", borderRadius:6, padding:"8px 12px",
                marginBottom:4, fontFamily:"'Roboto Mono',monospace", fontSize:11,
                color:"#81C995", border:"1px solid rgba(255,255,255,0.05)" }}>
                <span style={{ color:"#4285F4" }}>{p.method}</span>{" "}
                <span style={{ color:"#E8EAED" }}>{p.url}</span>
                {p.expectedStatus && <span style={{ color:"#FFAA00" }}> → {p.expectedStatus}</span>}
                {p.automated && <span style={{ color:"#00C896", marginLeft:8 }}>● automated</span>}
                {!p.automated && <span style={{ color:"#FF9800", marginLeft:8 }}>◉ manual</span>}
              </div>
            ))}
          </div>
          <div style={{ background:"rgba(0,200,150,0.05)", border:"1px solid rgba(0,200,150,0.12)",
            borderRadius:8, padding:"10px 12px", marginBottom:14 }}>
            <div style={{ fontSize:10, color:"#00C896", fontWeight:700, letterSpacing:"0.1em",
              marginBottom:4, fontFamily:"'Roboto Mono',monospace" }}>✦ REMEDIATION</div>
            <p style={{ fontSize:11.5, color:"rgba(255,255,255,0.6)", margin:0, lineHeight:1.5 }}>
              {risk.fix}
            </p>
          </div>
          <button onClick={(e) => { e.stopPropagation(); onRunTest(risk.id); }}
            style={{ background:"linear-gradient(135deg,#4285F4,#0D47A1)",
              color:"#fff", border:"none", borderRadius:8, padding:"8px 20px",
              fontSize:12, fontWeight:700, cursor:"pointer", fontFamily:"'Google Sans',sans-serif",
              letterSpacing:"0.05em",
              boxShadow:"0 4px 16px rgba(66,133,244,0.3)",
              transition:"all 0.2s" }}>
            ▶ RUN VERIFICATION TEST
          </button>
        </div>
      </div>
    </div>
  );
}

function Tag({ label, color }) {
  return (
    <span style={{ fontSize:10, color, background:`${color}15`, padding:"2px 7px",
      borderRadius:4, border:`1px solid ${color}30`, fontFamily:"'Roboto Mono',monospace",
      letterSpacing:"0.04em" }}>
      {label}
    </span>
  );
}

const CATS = ["All", "Configuration", "Authentication", "Infosec", "API Security"];
const SEVS = ["All", "high", "medium", "low"];

export default function App() {
  const [statuses, setStatuses] = useState(() =>
    Object.fromEntries(RISKS.map(r => [r.id, "idle"])));
  const [filter, setFilter] = useState({ sev:"All", cat:"All", query:"" });
  const [scanning, setScanning] = useState(false);
  const [elapsed, setElapsed] = useState(0);
  const timerRef = useRef(null);

  const score = (() => {
    const unprotected = RISKS.filter(r => statuses[r.id] !== "pass").reduce((s,r)=>s+r.cvss,0);
    return Math.round(100 - (unprotected / TOTAL_MAX) * 100);
  })();

  const protected_ = Object.values(statuses).filter(s=>s==="pass").length;
  const vulnerable = Object.values(statuses).filter(s=>s==="fail").length;
  const highUnprotected = RISKS.filter(r=>r.severity==="high" && statuses[r.id]==="fail").length;

  const runTest = (id) => {
    setStatuses(s=>({...s,[id]:"running"}));
    setTimeout(() => {
      setStatuses(s=>({...s,[id]: Math.random() > 0.35 ? "pass" : "fail"}));
    }, 1200 + Math.random()*800);
  };

  const runAll = () => {
    if (scanning) return;
    setScanning(true);
    setElapsed(0);
    setStatuses(s=>Object.fromEntries(Object.keys(s).map(k=>[k,"running"])));
    timerRef.current = setInterval(() => setElapsed(e=>e+1), 1000);
    RISKS.forEach((r,i) => {
      setTimeout(() => {
        setStatuses(s=>({...s,[r.id]: Math.random() > 0.35 ? "pass" : "fail"}));
        if (i === RISKS.length-1) {
          setScanning(false);
          clearInterval(timerRef.current);
        }
      }, 600 + i*220 + Math.random()*200);
    });
  };

  const resetAll = () => {
    clearInterval(timerRef.current);
    setScanning(false);
    setElapsed(0);
    setStatuses(Object.fromEntries(RISKS.map(r=>[r.id,"idle"])));
  };

  const visible = RISKS.filter(r => {
    const matchSev = filter.sev === "All" || r.severity === filter.sev;
    const matchCat = filter.cat === "All" || r.category === filter.cat;
    const matchQ = !filter.query || r.title.toLowerCase().includes(filter.query.toLowerCase())
      || r.id.toLowerCase().includes(filter.query.toLowerCase());
    return matchSev && matchCat && matchQ;
  });

  return (
    <div style={{ minHeight:"100vh", background:"#08101C",
      fontFamily:"'Google Sans',sans-serif", color:"#E8EAED" }}>
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&family=Roboto+Mono:wght@400;500;700&display=swap');
        @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }
        @keyframes pulseGlow { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.6;transform:scale(1.4)} }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
        @keyframes scanLine { 0%{top:0} 100%{top:100%} }
        ::-webkit-scrollbar { width:4px; background:#08101C }
        ::-webkit-scrollbar-thumb { background:#1E2D45; border-radius:4px }
        button:hover { filter: brightness(1.15) }
      `}</style>

      {/* Top Bar */}
      <div style={{ background:"linear-gradient(90deg, #0D1B2A 0%, #0A1628 100%)",
        borderBottom:"1px solid rgba(66,133,244,0.15)",
        padding:"16px 32px", display:"flex", alignItems:"center",
        gap:16, flexWrap:"wrap", position:"sticky", top:0, zIndex:100,
        backdropFilter:"blur(12px)" }}>
        <div style={{ display:"flex", alignItems:"center", gap:10 }}>
          <div style={{ width:36, height:36, borderRadius:10,
            background:"linear-gradient(135deg,#4285F4,#0D47A1)",
            display:"flex", alignItems:"center", justifyContent:"center",
            fontSize:18, boxShadow:"0 4px 14px rgba(66,133,244,0.4)" }}>🛡</div>
          <div>
            <div style={{ fontSize:15, fontWeight:700, letterSpacing:"-0.02em", lineHeight:1.2 }}>
              VAPT Antigravity
            </div>
            <div style={{ fontSize:10, color:"rgba(255,255,255,0.35)",
              fontFamily:"'Roboto Mono',monospace" }}>SixTee · 12 Risks · OWASP 2025</div>
          </div>
        </div>
        <div style={{ flex:1, minWidth:200 }}>
          <input placeholder="Search risks or IDs…"
            value={filter.query}
            onChange={e=>setFilter(f=>({...f,query:e.target.value}))}
            style={{ width:"100%", background:"rgba(255,255,255,0.04)",
              border:"1px solid rgba(255,255,255,0.1)", borderRadius:8,
              padding:"7px 14px", color:"#E8EAED", fontSize:13,
              outline:"none", fontFamily:"'Google Sans',sans-serif",
              boxSizing:"border-box" }} />
        </div>
        <div style={{ display:"flex", gap:8, marginLeft:"auto" }}>
          <button onClick={resetAll} style={{ background:"rgba(255,255,255,0.05)",
            color:"rgba(255,255,255,0.6)", border:"1px solid rgba(255,255,255,0.1)",
            borderRadius:8, padding:"8px 16px", cursor:"pointer", fontSize:12,
            fontFamily:"'Google Sans',sans-serif" }}>↺ Reset</button>
          <button onClick={runAll} disabled={scanning}
            style={{ background: scanning ? "rgba(66,133,244,0.3)" : "linear-gradient(135deg,#4285F4,#0D47A1)",
              color:"#fff", border:"none", borderRadius:8, padding:"8px 20px",
              cursor: scanning ? "not-allowed" : "pointer", fontSize:12, fontWeight:700,
              fontFamily:"'Google Sans',sans-serif", letterSpacing:"0.04em",
              boxShadow:"0 4px 16px rgba(66,133,244,0.3)", display:"flex", alignItems:"center", gap:6 }}>
            {scanning ? <>◌ Scanning… {elapsed}s</> : <>▶ Run Full Scan</>}
          </button>
        </div>
      </div>

      <div style={{ maxWidth:1280, margin:"0 auto", padding:"28px 24px" }}>

        {/* Stats Row */}
        <div style={{ display:"grid", gridTemplateColumns:"repeat(auto-fit,minmax(180px,1fr))",
          gap:16, marginBottom:24 }}>
          {[
            { label:"Security Score", val:<ScoreRing score={score}/>, wide:true },
            { label:"Protected", val:protected_, color:"#00C896" },
            { label:"Vulnerable", val:vulnerable, color:vulnerable>0?"#FF4444":"#00C896" },
            { label:"High Severity Open", val:highUnprotected, color:highUnprotected>0?"#FF4444":"#00C896" },
            { label:"Total Risks", val:12, color:"#4285F4" },
          ].map((s,i)=>(
            <div key={i} style={{ background:"linear-gradient(145deg,#161E2E,#111827)",
              border:"1px solid rgba(255,255,255,0.06)", borderRadius:12,
              padding:"20px", display:"flex", flexDirection:"column",
              alignItems:"center", justifyContent:"center",
              boxShadow:"0 4px 20px rgba(0,0,0,0.3)",
              animation:`slideUp 0.5s ease ${i*0.07}s both` }}>
              <div style={{ fontSize: s.wide ? undefined : 36, fontWeight:700,
                color: s.color || "#E8EAED", lineHeight:1, marginBottom:6 }}>
                {s.val}
              </div>
              <div style={{ fontSize:11, color:"rgba(255,255,255,0.35)", letterSpacing:"0.08em",
                fontFamily:"'Roboto Mono',monospace", textAlign:"center" }}>
                {s.label.toUpperCase()}
              </div>
            </div>
          ))}
        </div>

        {/* Filters */}
        <div style={{ display:"flex", gap:12, marginBottom:20, flexWrap:"wrap" }}>
          <div style={{ display:"flex", gap:4 }}>
            {SEVS.map(s=>(
              <button key={s} onClick={()=>setFilter(f=>({...f,sev:s}))}
                style={{ background: filter.sev===s ? SEV[s]?.color||"#4285F4" : "rgba(255,255,255,0.04)",
                  color: filter.sev===s ? "#fff" : "rgba(255,255,255,0.5)",
                  border:`1px solid ${filter.sev===s ? SEV[s]?.color||"#4285F4" : "rgba(255,255,255,0.08)"}`,
                  borderRadius:6, padding:"5px 12px", cursor:"pointer", fontSize:11,
                  fontWeight:600, fontFamily:"'Google Sans',sans-serif", letterSpacing:"0.05em",
                  transition:"all 0.2s" }}>
                {s === "All" ? "ALL SEVERITY" : s.toUpperCase()}
              </button>
            ))}
          </div>
          <div style={{ display:"flex", gap:4, flexWrap:"wrap" }}>
            {CATS.map(c=>(
              <button key={c} onClick={()=>setFilter(f=>({...f,cat:c}))}
                style={{ background: filter.cat===c ? "rgba(66,133,244,0.2)" : "rgba(255,255,255,0.03)",
                  color: filter.cat===c ? "#4285F4" : "rgba(255,255,255,0.4)",
                  border:`1px solid ${filter.cat===c ? "rgba(66,133,244,0.4)" : "rgba(255,255,255,0.06)"}`,
                  borderRadius:6, padding:"5px 12px", cursor:"pointer", fontSize:11,
                  fontFamily:"'Google Sans',sans-serif", transition:"all 0.2s" }}>
                {c}
              </button>
            ))}
          </div>
          <div style={{ marginLeft:"auto", fontSize:11, color:"rgba(255,255,255,0.25)",
            fontFamily:"'Roboto Mono',monospace", alignSelf:"center" }}>
            {visible.length} / 12 risks shown
          </div>
        </div>

        {/* Risk Grid */}
        <div style={{ display:"grid", gridTemplateColumns:"repeat(auto-fill,minmax(320px,1fr))", gap:14 }}>
          {visible.map((r,i) => (
            <RiskCard key={r.id} risk={r} status={statuses[r.id]}
              onRunTest={runTest} index={i} />
          ))}
        </div>

        {/* Footer */}
        <div style={{ marginTop:40, textAlign:"center", padding:"20px 0",
          borderTop:"1px solid rgba(255,255,255,0.05)" }}>
          <div style={{ fontSize:11, color:"rgba(255,255,255,0.2)",
            fontFamily:"'Roboto Mono',monospace", letterSpacing:"0.08em" }}>
            VAPT-SIXTEE · 12 RISKS · OWASP TOP 10 2025 · SCHEMA v3.4.0
          </div>
          <div style={{ fontSize:10, color:"rgba(255,255,255,0.12)", marginTop:4 }}>
            Google Antigravity Skill · Situation-Aware · Production-Ready
          </div>
        </div>
      </div>
    </div>
  );
}
