<?php

Blade::directive('useravatar', function () {
  return \RapydUser::user_avatar();
});