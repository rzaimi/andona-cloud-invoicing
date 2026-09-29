<?php

namespace App\Services;

/**
 * Resolves the hard dependencies between company modules declared in
 * config/modules.php ('requires' = all of, 'requires_any' = at least one of).
 * Dependent modules must never be enabled without their requirements.
 */
class ModuleDependencyService
{
    /**
     * Expand a module list with every transitively required module.
     * Only known modules (config modules.all) are added.
     *
     * @param  list<string>  $modules
     * @return list<string>
     */
    public function closure(array $modules): array
    {
        $all = config('modules.all', []);
        $requires = config('modules.requires', []);

        $result = array_values(array_intersect($all, $modules));
        $queue = $result;

        while ($queue !== []) {
            $module = array_shift($queue);

            foreach ($requires[$module] ?? [] as $dependency) {
                if (! in_array($dependency, $result, true) && in_array($dependency, $all, true)) {
                    $result[] = $dependency;
                    $queue[] = $dependency;
                }
            }
        }

        // Keep config order for a stable list.
        return array_values(array_intersect($all, $result));
    }

    /**
     * Hard dependencies missing from a selection: [module => [missing, ...]].
     *
     * @param  list<string>  $modules
     * @return array<string, list<string>>
     */
    public function missingRequired(array $modules): array
    {
        $missing = [];

        foreach ($modules as $module) {
            $absent = array_values(array_diff(config('modules.requires.'.$module, []), $modules));

            if ($absent !== []) {
                $missing[$module] = $absent;
            }
        }

        return $missing;
    }

    /**
     * Any-of dependencies with no option enabled: [module => [options, ...]].
     *
     * @param  list<string>  $modules
     * @return array<string, list<string>>
     */
    public function missingAnyOf(array $modules): array
    {
        $missing = [];

        foreach ($modules as $module) {
            $options = config('modules.requires_any.'.$module, []);

            if ($options !== [] && array_intersect($options, $modules) === []) {
                $missing[$module] = $options;
            }
        }

        return $missing;
    }

    /**
     * German validation messages for an inconsistent selection, one per
     * violated module, using the configured labels.
     *
     * @param  list<string>  $modules
     * @return list<string>
     */
    public function violationMessages(array $modules): array
    {
        $labels = config('modules.labels', []);
        $label = fn (string $module): string => '„'.($labels[$module] ?? $module).'“';
        $messages = [];

        foreach ($this->missingRequired($modules) as $module => $required) {
            $messages[] = $label($module).' benötigt '.implode(' und ', array_map($label, $required)).'.';
        }

        foreach ($this->missingAnyOf($modules) as $module => $options) {
            $messages[] = $label($module).' benötigt mindestens eines von '.implode(' oder ', array_map($label, $options)).'.';
        }

        return $messages;
    }
}
