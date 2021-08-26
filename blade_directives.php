<?php

Blade::directive('useravatar', function ($expression) {
  return \RapydUser::get_avatar($expression);
});
Blade::directive('usergroupavatar', function ($expression) {
  return \RapydUsergroups::get_avatar($expression);
});
