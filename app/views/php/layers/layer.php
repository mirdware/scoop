<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"/><link rel="author" href="<?php echo \Scoop\View\Helper::overt('humans.txt') ?>"/><meta name="viewport" content="width=device-width"><link rel="shortcut icon" type="image/x-icon" href="<?php echo \Scoop\View\Helper::overt('favicon.ico') ?>"/><link rel="stylesheet" href="<?php echo \Scoop\View\Helper::css(\Scoop\View\Helper::get('app.name').'.min.css') ?>"/><script type="text/javascript"> var root = "<?php echo ROOT ?>";</script><script src="<?php echo \Scoop\View\Helper::js(\Scoop\View\Helper::get('app.name').'.min.js') ?>" async></script><title><?php echo $title ?> » <?php echo \Scoop\View\Helper::get('app.name') ?></title></head><body><a href="https://github.com/mirdware/scoop" target="_blank"><img style="position:absolute;top:0;left:0;border:0" src="https://camo.githubusercontent.com/c6625ac1f3ee0a12250227cf83ce904423abf351/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f677261795f3664366436642e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_gray_6d6d6d.png"/></a><div id="main"><?php echo \Scoop\View\Helper::get('msg');\Scoop\View\Heritage::output() ?> </div></body></html> 