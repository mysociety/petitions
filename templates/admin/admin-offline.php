<form action="<?=$this->self_link?>" method="post">
<input type="hidden" name="offline_create" value="1">

<p>Use this page to create an online copy of a paper
petition, allowing them to be listed on your petition
site along with online petitions.</p>

<? if ($errors) { ?>
<div id="errors"><ul>
<li><?=join('</li> <li>', $errors)?></li>
</ul></div>
<? } ?>

<div id="admin_offline_petition">

<h3>Petition details</h3>

<p><label for="pet_content"><?
    global $petition_prefix;
    print $petition_prefix;
    if (OPTION_SITE_TYPE == 'multiple') {
        if (OPTION_SITE_DOMAINS) {
            $body = db_getRow('select id, name from body where ref=?', http_auth_user());
        } else {
            err("Whoops, something has gone wrong");
        }
        print "<input type='hidden' name='body' value='$body[id]' />";
        print $body['name'];
        echo ' to';
    }
?>
...</label>
<br /><input type="text" name="pet_content" id="pet_content" size="60" value="<?=htmlspecialchars($data['pet_content'])?>" aria-required="true" />
</p>
<p><label for="detail">More details about the petition: <small>(optional)</small></label><br>
    <textarea id="detail" name="detail" cols="40" rows="7"><?=htmlspecialchars($data['detail'])?></textarea></p>

<p><label for="rawdeadline">Date petition was received:</label>
<input type="text" name="rawdeadline" id="rawdeadline" size="15" value="<?=htmlspecialchars($data['rawdeadline'])?>" aria-required="true" /></p>

<p><label for="ref">Petition short name (6 to 16 letters):</label>
    <input type="text" name="ref" id="ref" size="16" value="<?=htmlspecialchars($data['ref'])?>" aria-required="true" />
</p>

<p><label for="category">Category:</label>
<select name="category" id="category">
<option value="">-- Select a category --</option><?
    foreach (cobrand_categories() as $id => $category) {
        if (!$id) continue;
        print '<option';
        if (array_key_exists('category', $data) && $id == $data['category'])
            print ' selected="selected"'; # I hate XHTML
        print ' value="' . $id . '">' . $category . '</option>';
    }
?>
</select></p>

<p>Number of signatures: <input type="text" name="offline_signers" size=4 value="<?=htmlspecialchars($data['offline_signers'])?>">

<p>Web page (e.g. scan of petition, or related details):
<input type="text" name="offline_link" size=40 value="<?=htmlspecialchars($data['offline_link'])?>">
<small>(optional)</small>

<p>Physical location of paper petition (internal use only):
<input type="text" name="offline_location" size=40 value="<?=htmlspecialchars($data['offline_location'])?>">
<small>(optional)</small>

</div>

<div id="admin_offline_creator">

<h3>Petition creator details</h3>

<p><label for="name">Name:</label> <input type="text" name="name" id="name" size="20" value="<?=htmlspecialchars($data['name'])?>" aria-required="true" /></p>
<p><label for="organisation">Organisation:</label> <input type="text" name="organisation" id="organisation" size="20" value="<?=htmlspecialchars($data['organisation'])?>" /> <small>(optional)</small></p>
<p><label for="address">Address:</label><br>
<textarea id="address" name="address" cols="30" rows="4" aria-required="true"><?=htmlspecialchars($data['address'])?></textarea></p>
<p><label for="postcode">Postcode:</label> <input type="text" name="postcode" id="postcode" size="10" value="<?=htmlspecialchars($data['postcode'])?>" aria-required="true" /></p>
<p><label for="telephone">Telephone number:</label> <input type="text" name="telephone" id="telephone" size="15" value="<?=htmlspecialchars($data['telephone'])?>" aria-required="true" /> <small>(optional)</small></p>
<p><label for="email">Email:</label> <input type="text" name="email" id="email" size="20" value="<?=htmlspecialchars($data['email'])?>" aria-required="true" /> <small>(optional)</small></p>
<p>&nbsp;</p>
<p align="right"><input type="submit" value="Create"></p>
</form>

</div>

