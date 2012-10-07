<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
  <title><?php echo SITE_NAME?> - <?php echo wash_utf8($PAGE_TITRE); ?></title>
  <?php include('template/head.tpl.php'); ?>
</head>
<body>
  <div id="page">
    <header>
      <h1><a href="<?php echo URL_ROOT?>"><?php echo SITE_NAME?></a></h1>
      <p class="banner"><a href="http://www.aeriesguard.com/forum/Jeux-video/Borderlines-Alpha-fermee">Open Beta</a></p>
<?php if(Member::get_current_user_id()) {?>
      <nav role="main">
        <ul>
          <li>[ <?php echo guess_time(time(), GUESS_DATETIME_LOCALE)?> ]</li>
          <li><a href="<?php echo Page::get_page_url('dashboard') ?>">Play</a></li>
          <li><a href="<?php echo Page::get_page_url('game_list') ?>">Games</a></li>
          <?php if( $unread_count = Message_player::db_get_unread_count($current_player->id) ) :?>
          <li><a href="<?php echo Page::get_page_url('conversation_list') ?>"><strong>Conversations (<?php echo $unread_count?>)</strong></a></li>
          <?php else:?>
          <li><a href="<?php echo Page::get_page_url('conversation_list') ?>">Conversations</a></li>
          <?php endif;?>
          <li><a href="<?php echo Page::get_page_url('mon-compte') ?>">Account</a></li>
          <li><a href="<?php echo Page::get_page_url('logout') ?>">Logout</a></li>
        </ul>
      </nav>
<?php }else {?>
      <form id="form_login" action="<?php echo get_page_url('login')?>" method="post">
        <p>
          <input type="text" name="email" value="Entrez votre email" onclick="if(this.value = 'Entrez votre email') this.value = ''" />
        </p>
        <p>
          <input type="password" name="pass" value="12345678" onclick="if(this.value = '12345678') this.value = ''"/>
        </p>
        <button type="submit" name="submit_login" value="Ok">Sign in</button>
      </form>
      <nav role="main">
        <ul>
          <li><a href="<?php echo Page::get_page_url('accueil') ?>">Home</a></li>
          <li><a href="<?php echo Page::get_page_url('register') ?>">Sign Up</a></li>
          <li><a href="<?php echo Page::get_page_url('rappel-identifiants') ?>">Forgotten password ?</a></li>
        </ul>
      </nav>
<?php } ?>


    </header>
    <section id="main">
<?php Page::display_messages();?>
      <div id="content" class="page-<?php echo get_current_page()?>">
<?php echo $PAGE_CONTENU; ?>
      </div>
    </section>
    <footer>

      <form action="<?php echo Page::get_url(PAGE_CODE, $_GET)?>" method="post">
        <p>2012<?php echo date('Y') != 2012?' - '.date('Y'):''?> &copy; Hypolite |
          <?php echo __('Change language:')?>
          <select name="locale">
            <?php foreach( explode(',', LOCALES) as $locale ) :?>
            <option value="<?php echo $locale?>"<?php echo $locale == LOCALE?' selected="selected"':''?>><?php echo __($locale)?></option>
            <?php endforeach;?>
          </select>
          <button type="submit" name="setlocale" value="1">Set</button>
        </p>
      </form>
    </footer>
<?php
  if(DEBUG_SQL) {
    mysql_log();
  }
?>
  </div>
</body>
</html>