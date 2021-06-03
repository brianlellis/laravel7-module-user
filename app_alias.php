<?php

return [
  //----------- CONTROLLERS
  'RapydUser'           => Rapyd\RapydUser::class,
  'RapydUsergroups'     => Rapyd\RapydUsergroups::class,

  //----------- MODELS
  'm_Usergroups'        => Rapyd\Model\Usergroups::class,
  'm_UsergroupUsers'    => Rapyd\Model\UsergroupUsers::class,
  'm_UsergroupType'     => Rapyd\Model\UsergroupType::class,
  'm_Roles'             => Rapyd\Model\Roles::class,
  'm_UserRoles'         => Rapyd\Model\UserRoles::class,
];