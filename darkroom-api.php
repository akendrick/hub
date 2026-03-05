<?php
/**
 * darkroom-api.php — REST API for the Darkroom process journal.
 * Auth: session (same login as the main dashboard).
 *
 * METHODS:  GET | POST | PATCH | DELETE
 * Resources: chemistry_types | negative_types | chemistry | paper
 *            support_paper   | carbon_tissue  | negative  | exposure | photo
 */
declare(strict_types=1);

// ── Output JSON for ALL errors including fatal/shutdown ───────
// Use HTTP 200 for application errors so the server never intercepts
// the response body (some hosts swallow 4xx/5xx bodies entirely).
ob_start();

register_shutdown_function(function(): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        ob_end_clean();
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => false, 'error' => 'PHP fatal: '.$err['message'].' in '.$err['file'].' line '.$err['line']]);
    } else {
        ob_end_flush();
    }
});

set_exception_handler(function(Throwable $e): void {
    ob_end_clean();
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['ok' => false, 'error' => get_class($e).': '.$e->getMessage()]);
    exit;
});

require __DIR__ . '/auth.php';
auth_require_api();

if (!file_exists(__DIR__ . '/db.php')) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'db.php not found on server — upload it']);
    exit;
}
require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$method = $_SERVER['REQUEST_METHOD'];
$res    = (string)($_GET['res'] ?? '');
$id     = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;
$body   = [];
if (in_array($method, ['POST','PUT','PATCH'], true))
    $body = (array)(json_decode((string)file_get_contents('php://input'), true) ?? []);

// ── Helpers ───────────────────────────────────────────────────
// All errors return HTTP 200 with ok:false so the server never
// intercepts and swallows the response body.
function ok(mixed $d): void  { echo json_encode(['ok'=>true,'data'=>$d],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function err(string $m, int $c=200): void { echo json_encode(['ok'=>false,'error'=>$m]); exit; }
function nf(mixed $v): ?float  { return ($v!==null&&$v!=='') ? (float)$v : null; }
function ni(mixed $v): ?int    { return ($v!==null&&$v!=='') ? (int)$v  : null; }
function ns(mixed $v): ?string { $s=trim((string)($v??'')); return $s!=='' ? $s : null; }
function nb(mixed $v): int     { return (int)(bool)$v; }

function patch_row(string $table, int $id, array $body, array $fields): void {
    $sets=[]; $vals=[];
    foreach ($fields as $f=>$cast) {
        if (!array_key_exists($f,$body)) continue;
        $sets[]="`$f`=?";
        $vals[]= match($cast) { 'f'=>nf($body[$f]),'i'=>ni($body[$f]),'b'=>nb($body[$f]),default=>ns($body[$f]) };
    }
    if ($sets) { $vals[]=$id; db()->prepare("UPDATE `$table` SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }
}

// ── Router ────────────────────────────────────────────────────
try {
    match($res) {
        'chemistry_types' => r_lookup($method,$id,$body,'chemistry_types'),
        'negative_types'  => r_lookup($method,$id,$body,'negative_types'),
        'chemistry'       => r_chemistry($method,$id,$body),
        'paper'           => r_paper($method,$id,$body),
        'support_paper'   => r_support_paper($method,$id,$body),
        'carbon_tissue'   => r_carbon_tissue($method,$id,$body),
        'negative'        => r_negative($method,$id,$body),
        'exposure'        => r_exposure($method,$id,$body),
        'photo'           => r_photo($method,$id,$body),
        default           => err('Unknown resource',404),
    };
} catch (PDOException $e) { err('Database error: '.$e->getMessage()); }

// ── Lookup tables ─────────────────────────────────────────────
function r_lookup(string $m,?int $id,array $body,string $t): void {
    if (!in_array($t,['chemistry_types','negative_types'],true)) err('Forbidden',403);
    $pdo=db();
    if ($m==='GET')         { ok($pdo->query("SELECT * FROM `$t` ORDER BY name")->fetchAll()); }
    if ($m==='POST')        { $n=ns($body['name']??null)??err('name required'); $pdo->prepare("INSERT INTO `$t`(name)VALUES(?)")->execute([$n]); ok(['id'=>(int)$pdo->lastInsertId(),'name'=>$n]); }
    if ($m==='PATCH'&&$id)  { $n=ns($body['name']??null)??err('name required'); $pdo->prepare("UPDATE `$t` SET name=? WHERE id=?")->execute([$n,$id]); ok(['updated'=>$id]); }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM `$t` WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}

// ── Chemistry ─────────────────────────────────────────────────
function r_chemistry(string $m,?int $id,array $body): void {
    $pdo=db();
    if ($m==='GET') {
        if ($id) {
            $st=$pdo->prepare("SELECT c.*,ct.name AS type_name,
                (SELECT GROUP_CONCAT(parent_id) FROM chemistry_lineage WHERE child_id=c.id) AS created_from_ids,
                (SELECT GROUP_CONCAT(child_id)  FROM chemistry_lineage WHERE parent_id=c.id) AS created_ids
                FROM chemistry c LEFT JOIN chemistry_types ct ON ct.id=c.type_id WHERE c.id=?");
            $st->execute([$id]); ok($st->fetch()?:err('Not found',404));
        }
        ok($pdo->query("SELECT c.*,ct.name AS type_name FROM chemistry c LEFT JOIN chemistry_types ct ON ct.id=c.type_id ORDER BY c.date_created DESC,c.id DESC")->fetchAll());
    }
    if ($m==='POST') {
        empty($body['date_created'])&&err('date_created required');
        $pdo->prepare("INSERT INTO chemistry(date_created,type_id,percent_solution,notes)VALUES(?,?,?,?)")
            ->execute([$body['date_created'],ni($body['type_id']??null),nf($body['percent_solution']??null),ns($body['notes']??null)]);
        $newId=(int)$pdo->lastInsertId();
        if (!empty($body['created_from_id']))
            $pdo->prepare("INSERT IGNORE INTO chemistry_lineage(parent_id,child_id)VALUES(?,?)")->execute([(int)$body['created_from_id'],$newId]);
        ok(['id'=>$newId]);
    }
    if ($m==='PATCH'&&$id) {
        patch_row('chemistry',$id,$body,['date_created'=>'s','type_id'=>'i','percent_solution'=>'f','notes'=>'s']);
        if (array_key_exists('created_from_id',$body)) {
            $pdo->prepare("DELETE FROM chemistry_lineage WHERE child_id=?")->execute([$id]);
            if ($body['created_from_id'])
                $pdo->prepare("INSERT IGNORE INTO chemistry_lineage(parent_id,child_id)VALUES(?,?)")->execute([(int)$body['created_from_id'],$id]);
        }
        ok(['updated'=>$id]);
    }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM chemistry WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}

// ── Paper ─────────────────────────────────────────────────────
function r_paper(string $m,?int $id,array $body): void {
    $pdo=db();
    $sel="SELECT p.*,CONCAT_WS(' ',ct.name,c.date_created) AS treatment_label FROM paper p LEFT JOIN chemistry c ON c.id=p.treatment_chemistry_id LEFT JOIN chemistry_types ct ON ct.id=c.type_id";
    if ($m==='GET') {
        if ($id) { $st=$pdo->prepare($sel." WHERE p.id=?"); $st->execute([$id]); ok($st->fetch()?:err('Not found',404)); }
        ok($pdo->query($sel." ORDER BY p.id DESC")->fetchAll());
    }
    if ($m==='POST') {
        $pdo->prepare("INSERT INTO paper(manufacturer,label,weight,hot_press,treatment_chemistry_id,notes)VALUES(?,?,?,?,?,?)")
            ->execute([ns($body['manufacturer']??null),ns($body['label']??null),nf($body['weight']??null),nb($body['hot_press']??false),ni($body['treatment_chemistry_id']??null),ns($body['notes']??null)]);
        ok(['id'=>(int)$pdo->lastInsertId()]);
    }
    if ($m==='PATCH'&&$id) { patch_row('paper',$id,$body,['manufacturer'=>'s','label'=>'s','weight'=>'f','hot_press'=>'b','treatment_chemistry_id'=>'i','notes'=>'s']); ok(['updated'=>$id]); }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM paper WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}

// ── Support Paper ─────────────────────────────────────────────
function r_support_paper(string $m,?int $id,array $body): void {
    $pdo=db();
    $sel="SELECT sp.*,p.manufacturer,p.label,p.weight,p.hot_press,CONCAT_WS(' ',p.manufacturer,p.label) AS paper_label FROM support_paper sp JOIN paper p ON p.id=sp.paper_id";
    if ($m==='GET') {
        if ($id) { $st=$pdo->prepare($sel." WHERE sp.id=?"); $st->execute([$id]); ok($st->fetch()?:err('Not found',404)); }
        ok($pdo->query($sel." ORDER BY sp.id DESC")->fetchAll());
    }
    if ($m==='POST') {
        empty($body['paper_id'])&&err('paper_id required');
        $pdo->prepare("INSERT INTO support_paper(paper_id,mark,notes)VALUES(?,?,?)")
            ->execute([ni($body['paper_id']),ns($body['mark']??null)??'',ns($body['notes']??null)]);
        ok(['id'=>(int)$pdo->lastInsertId()]);
    }
    if ($m==='PATCH'&&$id) { patch_row('support_paper',$id,$body,['paper_id'=>'i','mark'=>'s','notes'=>'s']); ok(['updated'=>$id]); }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM support_paper WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}

// ── Carbon Tissue ─────────────────────────────────────────────
function r_carbon_tissue(string $m,?int $id,array $body): void {
    $pdo=db();
    $sel="SELECT ct.*,cty.name AS chem_type FROM carbon_tissue ct LEFT JOIN chemistry c ON c.id=ct.chemistry_id LEFT JOIN chemistry_types cty ON cty.id=c.type_id";
    if ($m==='GET') {
        if ($id) { $st=$pdo->prepare($sel." WHERE ct.id=?"); $st->execute([$id]); ok($st->fetch()?:err('Not found',404)); }
        ok($pdo->query($sel." ORDER BY ct.date_poured DESC,ct.id DESC")->fetchAll());
    }
    if ($m==='POST') {
        empty($body['date_poured'])&&err('date_poured required');
        $pdo->prepare("INSERT INTO carbon_tissue(size,chemistry_id,amount_poured,date_poured,notes)VALUES(?,?,?,?,?)")
            ->execute([ns($body['size']??null),ni($body['chemistry_id']??null),ns($body['amount_poured']??null),$body['date_poured'],ns($body['notes']??null)]);
        ok(['id'=>(int)$pdo->lastInsertId()]);
    }
    if ($m==='PATCH'&&$id) { patch_row('carbon_tissue',$id,$body,['size'=>'s','chemistry_id'=>'i','amount_poured'=>'s','date_poured'=>'s','notes'=>'s']); ok(['updated'=>$id]); }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM carbon_tissue WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}

// ── Negative ──────────────────────────────────────────────────
function r_negative(string $m,?int $id,array $body): void {
    $pdo=db();
    $sel="SELECT n.*,nt.name AS type_name FROM negative n LEFT JOIN negative_types nt ON nt.id=n.type_id";
    if ($m==='GET') {
        if ($id) { $st=$pdo->prepare($sel." WHERE n.id=?"); $st->execute([$id]); ok($st->fetch()?:err('Not found',404)); }
        ok($pdo->query($sel." ORDER BY n.date_created DESC,n.id DESC")->fetchAll());
    }
    if ($m==='POST') {
        empty($body['date_created'])&&err('date_created required');
        $pdo->prepare("INSERT INTO negative(date_created,type_id,settings_notes)VALUES(?,?,?)")
            ->execute([$body['date_created'],ni($body['type_id']??null),ns($body['settings_notes']??null)]);
        ok(['id'=>(int)$pdo->lastInsertId()]);
    }
    if ($m==='PATCH'&&$id) { patch_row('negative',$id,$body,['date_created'=>'s','type_id'=>'i','settings_notes'=>'s']); ok(['updated'=>$id]); }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM negative WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}

// ── Exposure ──────────────────────────────────────────────────
function r_exposure(string $m,?int $id,array $body): void {
    $pdo=db();
    $sel="SELECT e.*,nt.name AS neg_type,n.date_created AS neg_date FROM exposure e LEFT JOIN negative n ON n.id=e.negative_id LEFT JOIN negative_types nt ON nt.id=n.type_id";

    $hydrate=function(array &$rows) use ($pdo): void {
        if (!$rows) return;
        $ids=implode(',',array_map(fn($r)=>(int)$r['id'],$rows));
        $map=[];
        foreach ($pdo->query("SELECT * FROM exposure_times WHERE exposure_id IN($ids) ORDER BY exposure_id,sort_order")->fetchAll() as $t)
            $map[(int)$t['exposure_id']][]=$t;
        foreach ($rows as &$r) $r['times']=$map[(int)$r['id']]??[];
    };

    if ($m==='GET') {
        if ($id) { $st=$pdo->prepare($sel." WHERE e.id=?"); $st->execute([$id]); $rows=[$st->fetch()?:err('Not found',404)]; $hydrate($rows); ok($rows[0]); }
        $rows=$pdo->query($sel." ORDER BY e.date_exposed DESC,e.id DESC")->fetchAll(); $hydrate($rows); ok($rows);
    }
    if ($m==='POST') {
        empty($body['date_exposed'])&&err('date_exposed required');
        $pdo->prepare("INSERT INTO exposure(date_exposed,test_strip,paper_soak_time,paper_soak_temp,hot_develop_time,hot_develop_temp,cool_develop_time,cool_develop_temp,negative_id,notes)VALUES(?,?,?,?,?,?,?,?,?,?)")
            ->execute([$body['date_exposed'],nb($body['test_strip']??false),ni($body['paper_soak_time']??null),nf($body['paper_soak_temp']??null),ni($body['hot_develop_time']??null),nf($body['hot_develop_temp']??null),ni($body['cool_develop_time']??null),nf($body['cool_develop_temp']??null),ni($body['negative_id']??null),ns($body['notes']??null)]);
        $newId=(int)$pdo->lastInsertId(); _save_times($pdo,$newId,$body['times']??[]); ok(['id'=>$newId]);
    }
    if ($m==='PATCH'&&$id) {
        patch_row('exposure',$id,$body,['date_exposed'=>'s','test_strip'=>'b','paper_soak_time'=>'i','paper_soak_temp'=>'f','hot_develop_time'=>'i','hot_develop_temp'=>'f','cool_develop_time'=>'i','cool_develop_temp'=>'f','negative_id'=>'i','notes'=>'s']);
        if (isset($body['times'])) { $pdo->prepare("DELETE FROM exposure_times WHERE exposure_id=?")->execute([$id]); _save_times($pdo,$id,$body['times']); }
        ok(['updated'=>$id]);
    }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM exposure WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}
function _save_times(PDO $pdo,int $eid,array $times): void {
    $st=$pdo->prepare("INSERT INTO exposure_times(exposure_id,duration_minutes,sort_order)VALUES(?,?,?)");
    foreach ($times as $i=>$t) { $d=nf($t['duration_minutes']??null); if ($d!==null&&$d>0) $st->execute([$eid,$d,$i]); }
}

// ── Photo ─────────────────────────────────────────────────────
function r_photo(string $m,?int $id,array $body): void {
    $pdo=db();
    $sel="SELECT ph.*,gct.name AS gelatin_type,CONCAT_WS(' ',p.manufacturer,p.label) AS paper_label,e.date_exposed AS exposure_date FROM photo ph LEFT JOIN chemistry gc ON gc.id=ph.gelatin_chemistry_id LEFT JOIN chemistry_types gct ON gct.id=gc.type_id LEFT JOIN paper p ON p.id=ph.paper_id LEFT JOIN exposure e ON e.id=ph.exposure_id";

    $hydrate=function(array &$rows) use ($pdo): void {
        if (!$rows) return;
        $ids=implode(',',array_map(fn($r)=>(int)$r['id'],$rows));
        $map=[];
        foreach ($pdo->query("SELECT pl.*,sp.mark AS sp_mark,CONCAT_WS(' ',pp.manufacturer,pp.label) AS sp_paper_label,nt.name AS neg_type,n.date_created AS neg_date,ct.size AS ct_size,ct.date_poured AS ct_date FROM photo_layer pl LEFT JOIN support_paper sp ON sp.id=pl.support_paper_id LEFT JOIN paper pp ON pp.id=sp.paper_id LEFT JOIN negative n ON n.id=pl.negative_id LEFT JOIN negative_types nt ON nt.id=n.type_id LEFT JOIN carbon_tissue ct ON ct.id=pl.carbon_tissue_id WHERE pl.photo_id IN($ids) ORDER BY pl.photo_id,pl.sort_order")->fetchAll() as $l)
            $map[(int)$l['photo_id']][]=$l;
        foreach ($rows as &$r) $r['layers']=$map[(int)$r['id']]??[];
    };

    if ($m==='GET') {
        if ($id) { $st=$pdo->prepare($sel." WHERE ph.id=?"); $st->execute([$id]); $rows=[$st->fetch()?:err('Not found',404)]; $hydrate($rows); ok($rows[0]); }
        $rows=$pdo->query($sel." ORDER BY ph.id DESC")->fetchAll(); $hydrate($rows); ok($rows);
    }
    if ($m==='POST') {
        $pdo->prepare("INSERT INTO photo(gelatin_chemistry_id,paper_id,amount_used,photo_size,date_sensitized,date_exposed,exposure_id,notes)VALUES(?,?,?,?,?,?,?,?)")
            ->execute([ni($body['gelatin_chemistry_id']??null),ni($body['paper_id']??null),ns($body['amount_used']??null),ns($body['photo_size']??null),ns($body['date_sensitized']??null),ns($body['date_exposed']??null),ni($body['exposure_id']??null),ns($body['notes']??null)]);
        $newId=(int)$pdo->lastInsertId(); _save_layers($pdo,$newId,$body['layers']??[]); ok(['id'=>$newId]);
    }
    if ($m==='PATCH'&&$id) {
        patch_row('photo',$id,$body,['gelatin_chemistry_id'=>'i','paper_id'=>'i','amount_used'=>'s','photo_size'=>'s','date_sensitized'=>'s','date_exposed'=>'s','exposure_id'=>'i','notes'=>'s']);
        if (isset($body['layers'])) { $pdo->prepare("DELETE FROM photo_layer WHERE photo_id=?")->execute([$id]); _save_layers($pdo,$id,$body['layers']); }
        ok(['updated'=>$id]);
    }
    if ($m==='DELETE'&&$id) { $pdo->prepare("DELETE FROM photo WHERE id=?")->execute([$id]); ok(['deleted'=>$id]); }
    err('Method not allowed',405);
}
function _save_layers(PDO $pdo,int $pid,array $layers): void {
    $st=$pdo->prepare("INSERT INTO photo_layer(photo_id,support_paper_id,negative_id,carbon_tissue_id,sort_order)VALUES(?,?,?,?,?)");
    foreach ($layers as $i=>$l) { $sp=ni($l['support_paper_id']??null); $neg=ni($l['negative_id']??null); $ct=ni($l['carbon_tissue_id']??null); if ($sp||$neg||$ct) $st->execute([$pid,$sp,$neg,$ct,$i]); }
}