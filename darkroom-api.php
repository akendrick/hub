<?php
/**
 * darkroom-api.php  —  REST API (session auth) for the Darkroom journal v2.
 * Resources: chemistry_types | negative_types | chemistry | paper |
 *            support_paper | carbon_tissue | negative | photo | photo_image
 * Note: exposure is gone — merged into photo.
 */
declare(strict_types=1);

ob_start();
register_shutdown_function(function(): void {
    $err = error_get_last();
    if ($err && in_array($err['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true)) {
        ob_end_clean(); if(!headers_sent()){http_response_code(200);header('Content-Type: application/json; charset=utf-8');}
        echo json_encode(['ok'=>false,'error'=>'PHP fatal: '.$err['message']]);
    } else { ob_end_flush(); }
});
set_exception_handler(function(Throwable $e): void {
    ob_end_clean(); if(!headers_sent()){http_response_code(200);header('Content-Type: application/json; charset=utf-8');}
    echo json_encode(['ok'=>false,'error'=>get_class($e).': '.$e->getMessage()]); exit;
});

require __DIR__ . '/auth.php';
auth_require_api();
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$method = $_SERVER['REQUEST_METHOD'];
$res    = (string)($_GET['res'] ?? '');
$id     = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;
$body   = [];
if (in_array($method,['POST','PUT','PATCH'],true) && !isset($_FILES['image']))
    $body = (array)(json_decode((string)file_get_contents('php://input'),true)??[]);

function ok(mixed $d): void  { echo json_encode(['ok'=>true,'data'=>$d],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function err(string $m,int $c=200): void { echo json_encode(['ok'=>false,'error'=>$m]); exit; }
function nf(mixed $v): ?float  { return ($v!==null&&$v!=='')?(float)$v:null; }
function ni(mixed $v): ?int    { return ($v!==null&&$v!=='')?(int)$v:null; }
function ns(mixed $v): ?string { $s=trim((string)($v??'')); return $s!==''?$s:null; }
function nb(mixed $v): int     { return (int)(bool)$v; }
function prow(string $t,int $id,array $b,array $f): void {
    $s=[]; $v=[];
    foreach($f as $k=>$c){if(!array_key_exists($k,$b))continue;$s[]="`$k`=?";$v[]=match($c){'f'=>nf($b[$k]),'i'=>ni($b[$k]),'b'=>nb($b[$k]),default=>ns($b[$k])};}
    if($s){$v[]=$id;db()->prepare("UPDATE `$t` SET ".implode(',',$s)." WHERE id=?")->execute($v);}
}

try {
    match($res){
        'chemistry_types' => r_lu($method,$id,$body,'chemistry_types'),
        'negative_types'  => r_lu($method,$id,$body,'negative_types'),
        'chemistry'       => r_chemistry($method,$id,$body),
        'paper'           => r_paper($method,$id,$body),
        'support_paper'   => r_sp($method,$id,$body),
        'carbon_tissue'   => r_ct($method,$id,$body),
        'negative'        => r_negative($method,$id,$body),
        'photo'           => r_photo($method,$id,$body),
        'photo_image'     => r_photo_image($method,$id),
        default           => err('Unknown resource'),
    };
} catch(PDOException $e){ err('Database error: '.$e->getMessage()); }

// ── Lookups ────────────────────────────────────────────────────
function r_lu(string $m,?int $id,array $b,string $t): void {
    if(!in_array($t,['chemistry_types','negative_types'],true))err('Forbidden');
    $pdo=db();
    if($m==='GET'){ok($pdo->query("SELECT * FROM `$t` ORDER BY name")->fetchAll());}
    if($m==='POST'){$n=ns($b['name']??null);if(!$n)err('name required');$pdo->prepare("INSERT INTO `$t`(name)VALUES(?)")->execute([$n]);ok(['id'=>(int)$pdo->lastInsertId(),'name'=>$n]);}
    if($m==='PATCH'&&$id){$n=ns($b['name']??null);if(!$n)err('name required');$pdo->prepare("UPDATE `$t` SET name=? WHERE id=?")->execute([$n,$id]);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM `$t` WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed');
}

// ── Chemistry ──────────────────────────────────────────────────
function r_chemistry(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT c.*,ct.name AS type_name,(SELECT GROUP_CONCAT(parent_id) FROM chemistry_lineage WHERE child_id=c.id) AS created_from_ids FROM chemistry c LEFT JOIN chemistry_types ct ON ct.id=c.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE c.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found'));}
        ok($pdo->query($q." ORDER BY c.date_created DESC,c.id DESC")->fetchAll());
    }
    if($m==='POST'){
        if(empty($b['date_created']))err('date_created required');
        $pdo->prepare("INSERT INTO chemistry(date_created,type_id,percent_solution,notes)VALUES(?,?,?,?)")->execute([$b['date_created'],ni($b['type_id']??null),nf($b['percent_solution']??null),ns($b['notes']??null)]);
        $nid=(int)$pdo->lastInsertId();
        if(!empty($b['created_from_id']))$pdo->prepare("INSERT IGNORE INTO chemistry_lineage(parent_id,child_id)VALUES(?,?)")->execute([(int)$b['created_from_id'],$nid]);
        ok(['id'=>$nid]);
    }
    if($m==='PATCH'&&$id){
        prow('chemistry',$id,$b,['date_created'=>'s','type_id'=>'i','percent_solution'=>'f','notes'=>'s']);
        if(array_key_exists('created_from_id',$b)){$pdo->prepare("DELETE FROM chemistry_lineage WHERE child_id=?")->execute([$id]);if($b['created_from_id'])$pdo->prepare("INSERT IGNORE INTO chemistry_lineage(parent_id,child_id)VALUES(?,?)")->execute([(int)$b['created_from_id'],$id]);}
        ok(['updated'=>$id]);
    }
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM chemistry WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed');
}

// ── Paper ──────────────────────────────────────────────────────
function r_paper(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT p.* FROM paper p";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE p.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found'));}
        ok($pdo->query($q." ORDER BY p.id DESC")->fetchAll());
    }
    if($m==='POST'){$pdo->prepare("INSERT INTO paper(manufacturer,label,weight,hot_press,notes)VALUES(?,?,?,?,?)")->execute([ns($b['manufacturer']??null),ns($b['label']??null),nf($b['weight']??null),nb($b['hot_press']??false),ns($b['notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()]);}
    if($m==='PATCH'&&$id){prow('paper',$id,$b,['manufacturer'=>'s','label'=>'s','weight'=>'f','hot_press'=>'b','notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM paper WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed');
}

// ── Support Paper ──────────────────────────────────────────────
function r_sp(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT sp.*,p.manufacturer,p.label,p.weight,p.hot_press,CONCAT_WS(' ',p.manufacturer,p.label) AS paper_label,CONCAT_WS(' ',ct.name,c.date_created) AS treatment_label FROM support_paper sp JOIN paper p ON p.id=sp.paper_id LEFT JOIN chemistry c ON c.id=sp.treatment_chemistry_id LEFT JOIN chemistry_types ct ON ct.id=c.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE sp.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found'));}
        ok($pdo->query($q." ORDER BY sp.mark ASC,sp.id DESC")->fetchAll());
    }
    if($m==='POST'){if(empty($b['paper_id']))err('paper_id required');$pdo->prepare("INSERT INTO support_paper(paper_id,mark,treatment_chemistry_id,notes)VALUES(?,?,?,?)")->execute([ni($b['paper_id']),ns($b['mark']??null)??'',ni($b['treatment_chemistry_id']??null),ns($b['notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()]);}
    if($m==='PATCH'&&$id){prow('support_paper',$id,$b,['paper_id'=>'i','mark'=>'s','treatment_chemistry_id'=>'i','notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM support_paper WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed');
}

// ── Carbon Tissue ──────────────────────────────────────────────
function r_ct(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT ct.*,cty.name AS chem_type FROM carbon_tissue ct LEFT JOIN chemistry c ON c.id=ct.chemistry_id LEFT JOIN chemistry_types cty ON cty.id=c.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE ct.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found'));}
        ok($pdo->query($q." ORDER BY ct.date_poured DESC,ct.id DESC")->fetchAll());
    }
    if($m==='POST'){if(empty($b['date_poured']))err('date_poured required');$pdo->prepare("INSERT INTO carbon_tissue(title_id,size,chemistry_id,amount_poured,date_poured,notes)VALUES(?,?,?,?,?,?)")->execute([ns($b['title_id']??null),ns($b['size']??null),ni($b['chemistry_id']??null),ns($b['amount_poured']??null),$b['date_poured'],ns($b['notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()]);}
    if($m==='PATCH'&&$id){prow('carbon_tissue',$id,$b,['title_id'=>'s','size'=>'s','chemistry_id'=>'i','amount_poured'=>'s','date_poured'=>'s','notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM carbon_tissue WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed');
}

// ── Negative ──────────────────────────────────────────────────
function r_negative(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT n.*,nt.name AS type_name FROM negative n LEFT JOIN negative_types nt ON nt.id=n.type_id";
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE n.id=?");$st->execute([$id]);ok($st->fetch()?:err('Not found'));}
        ok($pdo->query($q." ORDER BY n.date_created DESC,n.id DESC")->fetchAll());
    }
    if($m==='POST'){if(empty($b['date_created']))err('date_created required');$pdo->prepare("INSERT INTO negative(title_id,date_created,type_id,settings_notes)VALUES(?,?,?,?)")->execute([ns($b['title_id']??null),$b['date_created'],ni($b['type_id']??null),ns($b['settings_notes']??null)]);ok(['id'=>(int)$pdo->lastInsertId()]);}
    if($m==='PATCH'&&$id){prow('negative',$id,$b,['title_id'=>'s','date_created'=>'s','type_id'=>'i','settings_notes'=>'s']);ok(['updated'=>$id]);}
    if($m==='DELETE'&&$id){$pdo->prepare("DELETE FROM negative WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);}
    err('Method not allowed');
}

// ── Photo ─────────────────────────────────────────────────────
function r_photo(string $m,?int $id,array $b): void {
    $pdo=db();
    $q="SELECT ph.*,CONCAT_WS(' ',p.manufacturer,p.label) AS paper_label FROM photo ph LEFT JOIN paper p ON p.id=ph.paper_id";
    $hy=function(array &$rows) use ($pdo): void {
        if(!$rows) return;
        $ids=implode(',',array_map(fn($r)=>(int)$r['id'],$rows));
        // times
        $tmap=[];
        foreach($pdo->query("SELECT * FROM photo_times WHERE photo_id IN($ids) ORDER BY photo_id,sort_order")->fetchAll() as $t) $tmap[(int)$t['photo_id']][]=$t;
        // layers with full detail
        $lmap=[];
        foreach($pdo->query("SELECT pl.*,
            sp.mark AS sp_mark, CONCAT_WS(' ',pp.manufacturer,pp.label) AS sp_paper_label,
            pp.weight AS sp_weight, pp.hot_press AS sp_hot_press, sp.notes AS sp_notes,
            n.title_id AS neg_title_id, nt.name AS neg_type, n.date_created AS neg_date, n.settings_notes AS neg_notes,
            ct.title_id AS ct_title_id, ct.size AS ct_size, ct.date_poured AS ct_date, ct.amount_poured AS ct_amount, ct.notes AS ct_notes
          FROM photo_layer pl
          LEFT JOIN support_paper sp ON sp.id=pl.support_paper_id
          LEFT JOIN paper pp ON pp.id=sp.paper_id
          LEFT JOIN negative n ON n.id=pl.negative_id
          LEFT JOIN negative_types nt ON nt.id=n.type_id
          LEFT JOIN carbon_tissue ct ON ct.id=pl.carbon_tissue_id
          WHERE pl.photo_id IN($ids) ORDER BY pl.photo_id,pl.sort_order")->fetchAll() as $l) $lmap[(int)$l['photo_id']][]=$l;
        foreach($rows as &$r){ $r['times']=$tmap[(int)$r['id']]??[]; $r['layers']=$lmap[(int)$r['id']]??[]; }
    };
    if($m==='GET'){
        if($id){$st=$pdo->prepare($q." WHERE ph.id=?");$st->execute([$id]);$rows=[$st->fetch()?:err('Not found')];$hy($rows);ok($rows[0]);}
        $rows=$pdo->query($q." ORDER BY ph.id DESC")->fetchAll();$hy($rows);ok($rows);
    }
    if($m==='POST'){
        $pdo->prepare("INSERT INTO photo(title,paper_id,photo_size,date_sensitized,date_exposed,test_strip,paper_soak_time,paper_soak_temp,hot_develop_time,hot_develop_temp,cool_develop_time,cool_develop_temp,notes)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([ns($b['title']??null),ni($b['paper_id']??null),ns($b['photo_size']??null),ns($b['date_sensitized']??null),ns($b['date_exposed']??null),nb($b['test_strip']??false),ni($b['paper_soak_time']??null),nf($b['paper_soak_temp']??null),ni($b['hot_develop_time']??null),nf($b['hot_develop_temp']??null),ni($b['cool_develop_time']??null),nf($b['cool_develop_temp']??null),ns($b['notes']??null)]);
        $nid=(int)$pdo->lastInsertId();
        _stimes($pdo,$nid,$b['times']??[]);_slayers($pdo,$nid,$b['layers']??[]);ok(['id'=>$nid]);
    }
    if($m==='PATCH'&&$id){
        prow('photo',$id,$b,['title'=>'s','paper_id'=>'i','photo_size'=>'s','date_sensitized'=>'s','date_exposed'=>'s','test_strip'=>'b','paper_soak_time'=>'i','paper_soak_temp'=>'f','hot_develop_time'=>'i','hot_develop_temp'=>'f','cool_develop_time'=>'i','cool_develop_temp'=>'f','notes'=>'s']);
        if(isset($b['times'])){$pdo->prepare("DELETE FROM photo_times WHERE photo_id=?")->execute([$id]);_stimes($pdo,$id,$b['times']);}
        if(isset($b['layers'])){$pdo->prepare("DELETE FROM photo_layer WHERE photo_id=?")->execute([$id]);_slayers($pdo,$id,$b['layers']);}
        ok(['updated'=>$id]);
    }
    if($m==='DELETE'&&$id){
        // Delete image file if present
        $row=$pdo->prepare("SELECT image_path FROM photo WHERE id=?");$row->execute([$id]);
        if($r=$row->fetch()){_del_image($r['image_path']);}
        $pdo->prepare("DELETE FROM photo WHERE id=?")->execute([$id]);ok(['deleted'=>$id]);
    }
    err('Method not allowed');
}
function _stimes(PDO $p,int $pid,array $t): void { $s=$p->prepare("INSERT INTO photo_times(photo_id,duration_minutes,sort_order)VALUES(?,?,?)"); foreach($t as $i=>$x){$d=nf($x['duration_minutes']??null);if($d!==null&&$d>0)$s->execute([$pid,$d,$i]);} }
function _slayers(PDO $p,int $pid,array $layers): void { $s=$p->prepare("INSERT INTO photo_layer(photo_id,support_paper_id,negative_id,carbon_tissue_id,sort_order)VALUES(?,?,?,?,?)"); foreach($layers as $i=>$l){$sp=ni($l['support_paper_id']??null);$neg=ni($l['negative_id']??null);$ct=ni($l['carbon_tissue_id']??null);if($sp||$neg||$ct)$s->execute([$pid,$sp,$neg,$ct,$i]);} }

// ── Photo Image upload ─────────────────────────────────────────
function r_photo_image(string $m,?int $id): void {
    if($m!=='POST'||!$id)err('POST ?res=photo_image&id=N required');
    if(empty($_FILES['image']))err('No image file uploaded');
    $f=$_FILES['image'];
    if($f['error']!==UPLOAD_ERR_OK)err('Upload error '.$f['error']);
    $mime=mime_content_type($f['tmp_name']);
    if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))err('Only JPEG/PNG/WEBP accepted');
    $ext=match($mime){'image/png'=>'png','image/webp'=>'webp',default=>'jpg'};
    $dir=__DIR__.'/uploads/darkroom/';
    if(!is_dir($dir))mkdir($dir,0755,true);
    $name='photo-'.$id.'-'.time().'.'.$ext;
    // delete old
    $pdo=db();$old=$pdo->prepare("SELECT image_path FROM photo WHERE id=?");$old->execute([$id]);
    if($r=$old->fetch())_del_image($r['image_path']);
    if(!move_uploaded_file($f['tmp_name'],$dir.$name))err('Failed to save image');
    $path='uploads/darkroom/'.$name;
    $pdo->prepare("UPDATE photo SET image_path=? WHERE id=?")->execute([$path,$id]);
    ok(['image_path'=>$path]);
}
function _del_image(?string $path): void { if($path&&file_exists(__DIR__.'/'.$path))@unlink(__DIR__.'/'.$path); }
