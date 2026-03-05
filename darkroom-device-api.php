<?php
/**
 * darkroom-device-api.php  —  iOS / remote device REST API for the Darkroom journal.
 *
 * AUTH:  Authorization: Bearer kw_<64 hex>
 *   OR   ?api_key=kw_<64 hex>
 *
 * RESOURCES (all at same endpoint, distinguished by ?res=)
 *   chemistry_types | negative_types
 *   chemistry | paper | support_paper | carbon_tissue | negative | exposure | photo
 *
 * METHODS   GET list | GET ?id=N single | POST create | PATCH ?id=N update | DELETE ?id=N
 *
 * RESPONSE  { "ok": true, "data": … }  |  { "ok": false, "error": "…" }
 */
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_device_api();
require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$res    = (string)($_GET['res'] ?? '');
$id     = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;
$body   = [];
if (in_array($method,['POST','PUT','PATCH'],true))
    $body = (array)(json_decode((string)file_get_contents('php://input'),true)??[]);

function ok(mixed $d,int $c=200): void { http_response_code($c); echo json_encode(['ok'=>true,'data'=>$d],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); exit; }
function err(string $m,int $c=400): void { http_response_code($c); echo json_encode(['ok'=>false,'error'=>$m]); exit; }
function nf(mixed $v): ?float  { return ($v!==null&&$v!=='')?(float)$v:null; }
function ni(mixed $v): ?int    { return ($v!==null&&$v!=='')?(int)$v:null; }
function ns(mixed $v): ?string { $s=trim((string)($v??'')); return $s!==''?$s:null; }
function nb(mixed $v): int     { return (int)(bool)$v; }
function prow(string $t,int $id,array $b,array $f): void {
    $s=[]; $v=[];
    foreach($f as $k=>$c){ if(!array_key_exists($k,$b))continue; $s[]="`$k`=?"; $v[]=match($c){'f'=>nf($b[$k]),'i'=>ni($b[$k]),'b'=>nb($b[$k]),default=>ns($b[$k])}; }
    if($s){$v[]=$id; db()->prepare("UPDATE `$t` SET ".implode(',',$s)." WHERE id=?")->execute($v);}
}

try {
    match($res){
        'chemistry_types'=>r_lu($method,$id,$body,'chemistry_types'),
        'negative_types' =>r_lu($method,$id,$body,'negative_types'),
        'chemistry'      =>r_chemistry($method,$id,$body),
        'paper'          =>r_paper($method,$id,$body),
        'support_paper'  =>r_sp($method,$id,$body),
        'carbon_tissue'  =>r_ct($method,$id,$body),
        'negative'       =>r_negative($method,$id,$body),
        'exposure'       =>r_exposure($method,$id,$body),
        'photo'          =>r_photo($method,$id,$body),
        default          =>err('Unknown resource',404),
    };
} catch(PDOException $e){ err('Database error: '.$e->getMessage(),500); }

function r_lu(string $m,?int $id,array $b,string $t): void {
    if(!in_array($t,['chemistry_types','negative_types'],true))err('Forbidden',403);
    $pdo=db();
    if($m==='GET'){ok($pdo->query("SELECT * FROM `$t` ORDER BY name")->fetchAll());}
    if($m==='POST'){$n=ns($b['name']??null);if(!$n)err('name required');$pdo->prepare("INSERT INTO `$t`(name)VALUES(?)")->execute([$n]);ok(['id'=>(int)$pdo->lastInsertId(),'name'=>$n],201);}
    if($m==='PATCH'&&$id){$n=ns($b['name']??null);if(!$n)err('name required');$pdo->prepare("UPDATE `$t` SET name=? WHERE id=?")->execute([$n,$id]);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM `$t` WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}

function r_chemistry(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT c.*,ct.name AS type_name,
        (SELECT GROUP_CONCAT(parent_id) FROM chemistry_lineage WHERE child_id=c.id) AS created_from_ids
        FROM chemistry c LEFT JOIN chemistry_types ct ON ct.id=c.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE c.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found',404));}
        ok($pdo->query($q." ORDER BY c.date_created DESC,c.id DESC")->fetchAll());
    }
    if($m==='POST'){
        if(empty($b['date_created']))err('date_created required');
        $pdo->prepare("INSERT INTO chemistry(date_created,type_id,percent_solution,notes)VALUES(?,?,?,?)")
            ->execute([$b['date_created'],ni($b['type_id']??null),nf($b['percent_solution']??null),ns($b['notes']??null)]);
        $nid=(int)$pdo->lastInsertId();
        if(!empty($b['created_from_id']))$pdo->prepare("INSERT IGNORE INTO chemistry_lineage(parent_id,child_id)VALUES(?,?)")->execute([(int)$b['created_from_id'],$nid]);
        ok(['id'=>$nid],201);
    }
    if($m==='PATCH'&&$id){
        prow('chemistry',$id,$b,['date_created'=>'s','type_id'=>'i','percent_solution'=>'f','notes'=>'s']);
        if(array_key_exists('created_from_id',$b)){$pdo->prepare("DELETE FROM chemistry_lineage WHERE child_id=?")->execute([$id]);if($b['created_from_id'])$pdo->prepare("INSERT IGNORE INTO chemistry_lineage(parent_id,child_id)VALUES(?,?)")->execute([(int)$b['created_from_id'],$id]);}
        ok(['updated'=>$id]);
    }
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM chemistry WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}

function r_paper(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT p.*,CONCAT_WS(' ',ct.name,c.date_created) AS treatment_label FROM paper p LEFT JOIN chemistry c ON c.id=p.treatment_chemistry_id LEFT JOIN chemistry_types ct ON ct.id=c.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE p.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found',404));}
        ok($pdo->query($q." ORDER BY p.id DESC")->fetchAll());
    }
    if($m==='POST'){$pdo->prepare("INSERT INTO paper(manufacturer,label,weight,hot_press,treatment_chemistry_id,notes)VALUES(?,?,?,?,?,?)")->execute([ns($b['manufacturer']??null),ns($b['label']??null),nf($b['weight']??null),nb($b['hot_press']??false),ni($b['treatment_chemistry_id']??null),ns($b['notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()],201);}
    if($m==='PATCH'&&$id){prow('paper',$id,$b,['manufacturer'=>'s','label'=>'s','weight'=>'f','hot_press'=>'b','treatment_chemistry_id'=>'i','notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM paper WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}

function r_sp(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT sp.*,p.manufacturer,p.label,p.weight,p.hot_press,CONCAT_WS(' ',p.manufacturer,p.label) AS paper_label FROM support_paper sp JOIN paper p ON p.id=sp.paper_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE sp.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found',404));}
        ok($pdo->query($q." ORDER BY sp.mark ASC,sp.id DESC")->fetchAll());
    }
    if($m==='POST'){if(empty($b['paper_id']))err('paper_id required');$pdo->prepare("INSERT INTO support_paper(paper_id,mark,notes)VALUES(?,?,?)")->execute([ni($b['paper_id']),ns($b['mark']??null)??'',ns($b['notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()],201);}
    if($m==='PATCH'&&$id){prow('support_paper',$id,$b,['paper_id'=>'i','mark'=>'s','notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM support_paper WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}

function r_ct(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT ct.*,cty.name AS chem_type FROM carbon_tissue ct LEFT JOIN chemistry c ON c.id=ct.chemistry_id LEFT JOIN chemistry_types cty ON cty.id=c.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE ct.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found',404));}
        ok($pdo->query($q." ORDER BY ct.date_poured DESC,ct.id DESC")->fetchAll());
    }
    if($m==='POST'){if(empty($b['date_poured']))err('date_poured required');$pdo->prepare("INSERT INTO carbon_tissue(size,chemistry_id,amount_poured,date_poured,notes)VALUES(?,?,?,?,?)")->execute([ns($b['size']??null),ni($b['chemistry_id']??null),ns($b['amount_poured']??null),$b['date_poured'],ns($b['notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()],201);}
    if($m==='PATCH'&&$id){prow('carbon_tissue',$id,$b,['size'=>'s','chemistry_id'=>'i','amount_poured'=>'s','date_poured'=>'s','notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM carbon_tissue WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}

function r_negative(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT n.*,nt.name AS type_name FROM negative n LEFT JOIN negative_types nt ON nt.id=n.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE n.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found',404));}
        ok($pdo->query($q." ORDER BY n.date_created DESC,n.id DESC")->fetchAll());
    }
    if($m==='POST'){if(empty($b['date_created']))err('date_created required');$pdo->prepare("INSERT INTO negative(date_created,type_id,settings_notes)VALUES(?,?,?)")->execute([$b['date_created'],ni($b['type_id']??null),ns($b['settings_notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()],201);}
    if($m==='PATCH'&&$id){prow('negative',$id,$b,['date_created'=>'s','type_id'=>'i','settings_notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM negative WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}

function r_exposure(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT e.*,nt.name AS neg_type,n.date_created AS neg_date FROM exposure e LEFT JOIN negative n ON n.id=e.negative_id LEFT JOIN negative_types nt ON nt.id=n.type_id";
    $hy=function(array &$rows) use ($pdo): void {
        if(!$rows)return;
        $ids=implode(',',array_map(fn($r)=>(int)$r['id'],$rows)); $map=[];
        foreach($pdo->query("SELECT * FROM exposure_times WHERE exposure_id IN($ids) ORDER BY exposure_id,sort_order")->fetchAll() as $t) $map[(int)$t['exposure_id']][]=$t;
        foreach($rows as &$r) $r['times']=$map[(int)$r['id']]??[];
    };
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE e.id=?");$st->execute([$id]);$rows=[$st->fetch()?:err('Not found',404)];$hy($rows);ok($rows[0]);}
        $rows=$pdo->query($q." ORDER BY e.date_exposed DESC,e.id DESC")->fetchAll();$hy($rows);ok($rows);
    }
    if($m==='POST'){
        if(empty($b['date_exposed']))err('date_exposed required');
        $pdo->prepare("INSERT INTO exposure(date_exposed,test_strip,paper_soak_time,paper_soak_temp,hot_develop_time,hot_develop_temp,cool_develop_time,cool_develop_temp,negative_id,notes)VALUES(?,?,?,?,?,?,?,?,?,?)")
            ->execute([$b['date_exposed'],nb($b['test_strip']??false),ni($b['paper_soak_time']??null),nf($b['paper_soak_temp']??null),ni($b['hot_develop_time']??null),nf($b['hot_develop_temp']??null),ni($b['cool_develop_time']??null),nf($b['cool_develop_temp']??null),ni($b['negative_id']??null),ns($b['notes']??null)]);
        $nid=(int)$pdo->lastInsertId(); _stimes($pdo,$nid,$b['times']??[]); ok(['id'=>$nid],201);
    }
    if($m==='PATCH'&&$id){
        prow('exposure',$id,$b,['date_exposed'=>'s','test_strip'=>'b','paper_soak_time'=>'i','paper_soak_temp'=>'f','hot_develop_time'=>'i','hot_develop_temp'=>'f','cool_develop_time'=>'i','cool_develop_temp'=>'f','negative_id'=>'i','notes'=>'s']);
        if(isset($b['times'])){$pdo->prepare("DELETE FROM exposure_times WHERE exposure_id=?")->execute([$id]);_stimes($pdo,$id,$b['times']);}
        ok(['updated'=>$id]);
    }
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM exposure WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}
function _stimes(PDO $p,int $eid,array $t): void { $s=$p->prepare("INSERT INTO exposure_times(exposure_id,duration_minutes,sort_order)VALUES(?,?,?)"); foreach($t as $i=>$x){$d=nf($x['duration_minutes']??null);if($d!==null&&$d>0)$s->execute([$eid,$d,$i]);} }

function r_photo(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT ph.*,gct.name AS gelatin_type,CONCAT_WS(' ',p.manufacturer,p.label) AS paper_label,e.date_exposed AS exposure_date FROM photo ph LEFT JOIN chemistry gc ON gc.id=ph.gelatin_chemistry_id LEFT JOIN chemistry_types gct ON gct.id=gc.type_id LEFT JOIN paper p ON p.id=ph.paper_id LEFT JOIN exposure e ON e.id=ph.exposure_id";
    $hy=function(array &$rows) use ($pdo): void {
        if(!$rows)return;
        $ids=implode(',',array_map(fn($r)=>(int)$r['id'],$rows)); $map=[];
        foreach($pdo->query("SELECT pl.*,sp.mark AS sp_mark,CONCAT_WS(' ',pp.manufacturer,pp.label) AS sp_paper_label,nt.name AS neg_type,n.date_created AS neg_date,ct.size AS ct_size,ct.date_poured AS ct_date FROM photo_layer pl LEFT JOIN support_paper sp ON sp.id=pl.support_paper_id LEFT JOIN paper pp ON pp.id=sp.paper_id LEFT JOIN negative n ON n.id=pl.negative_id LEFT JOIN negative_types nt ON nt.id=n.type_id LEFT JOIN carbon_tissue ct ON ct.id=pl.carbon_tissue_id WHERE pl.photo_id IN($ids) ORDER BY pl.photo_id,pl.sort_order")->fetchAll() as $l) $map[(int)$l['photo_id']][]=$l;
        foreach($rows as &$r) $r['layers']=$map[(int)$r['id']]??[];
    };
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE ph.id=?");$st->execute([$id]);$rows=[$st->fetch()?:err('Not found',404)];$hy($rows);ok($rows[0]);}
        $rows=$pdo->query($q." ORDER BY ph.id DESC")->fetchAll();$hy($rows);ok($rows);
    }
    if($m==='POST'){
        $pdo->prepare("INSERT INTO photo(gelatin_chemistry_id,paper_id,amount_used,photo_size,date_sensitized,date_exposed,exposure_id,notes)VALUES(?,?,?,?,?,?,?,?)")
            ->execute([ni($b['gelatin_chemistry_id']??null),ni($b['paper_id']??null),ns($b['amount_used']??null),ns($b['photo_size']??null),ns($b['date_sensitized']??null),ns($b['date_exposed']??null),ni($b['exposure_id']??null),ns($b['notes']??null)]);
        $nid=(int)$pdo->lastInsertId(); _slayers($pdo,$nid,$b['layers']??[]); ok(['id'=>$nid],201);
    }
    if($m==='PATCH'&&$id){
        prow('photo',$id,$b,['gelatin_chemistry_id'=>'i','paper_id'=>'i','amount_used'=>'s','photo_size'=>'s','date_sensitized'=>'s','date_exposed'=>'s','exposure_id'=>'i','notes'=>'s']);
        if(isset($b['layers'])){$pdo->prepare("DELETE FROM photo_layer WHERE photo_id=?")->execute([$id]);_slayers($pdo,$id,$b['layers']);}
        ok(['updated'=>$id]);
    }
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM photo WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed',405);
}
function _slayers(PDO $p,int $pid,array $layers): void { $s=$p->prepare("INSERT INTO photo_layer(photo_id,support_paper_id,negative_id,carbon_tissue_id,sort_order)VALUES(?,?,?,?,?)"); foreach($layers as $i=>$l){$sp=ni($l['support_paper_id']??null);$neg=ni($l['negative_id']??null);$ct=ni($l['carbon_tissue_id']??null);if($sp||$neg||$ct)$s->execute([$pid,$sp,$neg,$ct,$i]);} }
