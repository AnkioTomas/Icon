<?php
return array (
  'debug' => true,
  'timezone' => 'Asia/Shanghai',
  'default_route' => false,
  'domain' => 
  array (
    0 => '0.0.0.0',
  ),
  'version' => '1.0.0',
  'ip' => '127.0.0.1',
  'port' => 10211,
  'framework_start' => 
  array (
    0 => 'nova\\plugin\\tpl\\Handler',
  ),
  'waf' => 
  array (
    'useRule' => true,
    'useRateLimit' => true,
    'useFailedFlood' => true,
    'rateLimit' => 5,
    'basePenaltySeconds' => 600,
    'failureThreshold' => 10,
  ),
);