<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace Swagger\Processors;

use Swagger\Annotations\OpenApi;
use Swagger\Analysis;
use Swagger\Context;

/**
 * Merge all @OAS\OpenApi annotations into one.
 */
class MergeIntoOpenApi
{
    public function __invoke(Analysis $analysis)
    {
        // Auto-create the OpenApi annotation.
        if (!$analysis->openapi) {
            $context = new Context(['analysis' => $analysis]);
            $analysis->addAnnotation(new OpenApi(['_context' => $context]), $context);
        }
        $openapi = $analysis->openapi;
        $openapi->_analysis = $analysis;

        // Merge annotations into the target openapi
        $merge = [];
        $classes = array_keys(OpenApi::$_nested);
        foreach ($analysis->annotations as $annotation) {
            if ($annotation === $openapi) {
                continue;
            }
            if ($annotation instanceof OpenApi) {
                $paths = $annotation->paths;
                unset($annotation->paths);
                $openapi->mergeProperties($annotation);
                foreach ($paths as $path) {
                    $openapi->paths[] = $path;
                }
            } elseif (in_array(get_class($annotation), $classes) && property_exists($annotation, '_context') && $annotation->_context->is('nested') === false) { // A top level annotation.
                // Also merge @OAS\Info, @OAS\Server and other directly nested annotations.
                $merge[] = $annotation;
            }
        }
        $openapi->merge($merge, true);
    }
}
