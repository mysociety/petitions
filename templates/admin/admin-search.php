<?

if (!count($out) || count($out['petitions']) || $out['signers']['confirmed'] || $out['signers']['unconfirmed']) {
    if (isset($out['petitions'])) {
?>

<h3>Petitions</h3>
<table>
    <tr><td></td><th>Email</th><th>Name</th><th>Petition</th><th>Creation time</th><th>Actions</th></tr>
<?
        foreach ($out['petitions'] as $r) {
?>
    <tr><td></td><td><?=privacy($r['email'])?></td><td><?=htmlspecialchars($r['name'])?></td><td><?=$r['ref']?></td>
        <td><?=prettify($r['creationtime'])?></td>
        <td>
<?          if ($r['status'] == 'sentconfirm') { ?>
                <form name="petition_admin_search" method="post" action="<?=$this->self_link?>"><input type="hidden" name="search" value="<?=htmlspecialchars($search)?>">
                <input type="hidden" name="confirm_petition_id" value="<?=$r['id']?>"><input type="submit" name="confirm" value="Confirm petition, move to 'draft'">
                </form>
<?          } else { ?>
                <a href="?page=pet&amp;petition=<?=$r['ref']?>">admin</a>
<?          } ?>
        </td>
    </tr>
<?
        }
?>
</table>
<?
    }
    if (isset($out['signers']['confirmed'])) {
?>

<h3>Signature removal</h3>
<form name="petition_admin_signature_removal" method="post" action="<?=$this->self_link?>">
<input type="hidden" name="search" value="<?=htmlspecialchars($search)?>">
<table>
    <tr><td></td><th>Email</th><th>Name</th><th>Petition</th><th>Creation time</th></tr>
<?
        foreach ($out['signers']['confirmed'] as $r) {
?>
    <tr><td><input type="checkbox" name="update_signer[]" value="<?=$r['id']?>"></td>
        <td><?=privacy($r['email'])?></td><td><?=htmlspecialchars($r['name'])?></td><td><a href="<?=OPTION_BASE_URL."/$r[ref]/"?>"><?=$r['ref']?></a></td>
        <td><?=prettify($r['signtime'])?></td>
    </tr>
<?
        }
?>
</table>
<input type="hidden" name="delete_all" value="1">
<p><input type="submit" value="Remove all ticked"></p>
</form>
<?
    }
    if (isset($out['signers']['unconfirmed'])) {
?>

<h3>Signature confirmation</h3>
<form name="petition_admin_signature_confirmation" method="post" action="<?=$this->self_link?>">
<input type="hidden" name="search" value="<?=htmlspecialchars($search)?>">
<table>
<tr><td></td><th>Email</th><th>Name</th><th>Petition</th><th>Creation time</th></tr>
<?
        foreach ($out['signers']['confirmed'] as $r) {
?>
    <tr><td><input type="checkbox" name="update_signer[]" value="<?=$r['id']?>"></td>
        <td><?=privacy($r['email'])?></td><td><?=htmlspecialchars($r['name'])?></td><td><a href="<?=OPTION_BASE_URL."/$r[ref]/"?>"><?=$r['ref']?></a></td>
        <td><?=prettify($r['signtime'])?></td>
    </tr>
<?
        }
?>
</table>
<input type="hidden" name="confirm_all" value="1">
<p><input type="submit" value="Confirm all ticked"></p>
</form>
<?
    }
} else {
?>
<p><em>No matches</em></p>
<?
}
?>

