<?php

Blade::directive('useravatar', function () {
  $user = \Auth::user();
  if($user) {
    if ($user->avatar) {
      return '<img src="'.$avatar_path.'" alt="User Avatar" class="userpic brround">';
    } else {
      $initials = $user->name_first[0].$user->name_last[0];
      return '<div class="userpic brround">'.$initials.'</div>';
    }
  } else {
    $domain_source = \SettingsSite::get('system_policy_domain_source');
    return '<div class="userpic brround">'.($domain_source ?? 'NA').'</div>';
  }
});