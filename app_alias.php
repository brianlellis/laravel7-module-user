<?php

return [
  //----------- CONTROLLERS
  'RapydUser'           => Rapyd\RapydUser::class,
  'RapydUsergroups'     => Rapyd\RapydUsergroups::class,
  'SsoServer'           => Rapyd\Sso\SsoServer::class,
  'SsoBroker'           => Rapyd\Sso\SsoBroker::class,
  'SsoBrokerServer'     => Rapyd\Sso\SsoBrokerServer::class,


  //----------- MODELS
  'm_Usergroups'        => Rapyd\Model\Usergroups::class,
  'm_UsergroupUsers'    => Rapyd\Model\UsergroupUsers::class,
  'm_UsergroupType'     => Rapyd\Model\UsergroupType::class,
  'm_Roles'             => Spatie\Permission\Models\Role::class,
  'm_UserRoles'         => Rapyd\Model\UserRoles::class,
  'm_UserPageVisits'    => Rapyd\Model\UserPageVisits::class,
];