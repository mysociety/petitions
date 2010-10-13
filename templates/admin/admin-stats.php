<form method="post" action="<?=$this->self_link?>">
<p>Date range statistics &ndash;
<label for="date_from">From:</label> <input type="text" id="date_from" name="from" value="<?=htmlspecialchars($from)?>">
<label for="date_to">To:</label> <input type="text" id="date_to" name="to" value="<?=htmlspecialchars($to)?>">
<input type="submit" value="Lookup">
</form>
        
<p>Summary statistics last updated: <?=$statsdate?>

<h2>Petitions</h2>

<p><?=$counts['live']?> live, <?=$counts['finished']?> finished,
<?=$counts['draft']?> draft, <?=$counts['rejectedonce']?> rejected once,
<?=$counts['resubmitted']?> resubmitted, <?=$counts['rejected']?> rejected again =
<strong><?=$counts['all_confirmed']?></strong> total with confirmed emails<br>

With unconfirmed emails: <?=$counts['unconfirmed']?> not sent,
<?=$counts['failedconfirm']?> failed send, <?=$counts['sentconfirm']?> sent =
<strong><?=$counts['all_unconfirmed']?></strong> total with unconfirmed emails

<p><img style="max-width:100%" src="pet-live-creation.png" alt="Graph of petition status by creation date">

<h2>Signatures</h2>

<p><?=$signatures_confirmed?> confirmed signatures (<?=$signers?> unique emails
in past year), <?=$signatures_unconfirmed?> unconfirmed

<p><img style="max-width:100%" src="pet-live-signups.png" alt="Graph of signers across whole site">

<h2>Responses</h2>

<p><?=$responses?> responses sent, to <?=$unique_responses?> unique petitions

