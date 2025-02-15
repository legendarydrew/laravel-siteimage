<?php

use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;

try
{
    return RectorConfig::configure()
                       ->withPaths(['src'])
                       ->withPhpSets(php83: true)
                       ->withPreparedSets(
                           deadCode: true,
                           codeQuality: true,
                           codingStyle: true,
                           typeDeclarations: true,
                           privatization: true,
                           naming: true,
                       );
}
catch (InvalidConfigurationException $e)
{
    // Nothing to do.
}
