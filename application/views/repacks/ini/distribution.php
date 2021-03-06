<?php
$r = $repack;
if ($r->addons_collection_url) {
    $firstrun_url = str_replace("https://", "http://", $r->releaseUrl()) . '/firstrun';
} else {
    $firstrun_url = null;
}
$dist_id = "byob-{$r->profile->screen_name}-{$r->short_name}";
$partner_id = 'byob' . $r->profile->screen_name;
?>
; Partner distribution.ini file for "<?= $r->title ?>"
; Author email: <?= @$r->profile->logins[0]->email . "\n" ?>
; UUID: <?= $r->uuid . "\n" ?>

[Global]
id=<?=$dist_id ."\n" ?>
version=<?= $r->version . "\n" ?>
about=<?= $r->title . "\n" ?>

[Preferences]
app.partner.<?= $partner_id ?>=<?= $partner_id . "\n" ?>
<?php if ($firstrun_url): ?>
startup.homepage_welcome_url="<?= $firstrun_url ?>"
<?php endif ?>

<?php
$bookmarks = $r->bookmarks;
if (!empty($r->addons_collection_url)) {
    // bug 511869: If there's an addon collection URL, inject a 
    // "Recommended Addons" bookmark into the toolbar.
    $bookmarks['toolbar']['items'][] = array(
        'id'    => 'recommended-addons',
        'type'  => 'bookmark',
        'title' => 'Recommended Addons',
        'description' => 'Recommended Addons',
        'link'  => $r->addons_collection_url,
    );
}
?>
<?php foreach (array('menu', 'toolbar') as $kind): ?>
<?php if (!empty($bookmarks[$kind]) && !empty($bookmarks[$kind]['items'])): ?>
<?php 
    View::factory('repacks/ini/bookmarks', array(
        'set_id' => ucfirst($kind), 
        'bookmarks' => $bookmarks[$kind]['items']
    ))->render(TRUE); 
?>
<?php endif ?>
<?php endforeach ?>

# <? # do not edit this line, or add newlines after it ?>
