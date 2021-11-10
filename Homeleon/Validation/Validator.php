<?php

namespace Homeleon\Validation;

use InvalidRuleException;

class Validator
{
    private array $ruleResponses = [];

    public function __construct()
    {
        $fileRules = __DIR__ . '/rules.php';
        $this->ruleResponses = file_exists($fileRules) ? require $fileRules : [];
    }

    public function __invoke($data, $rules)
    {
        return $this->validate($data, $rules);
    }

    public function validate($data, $rules)
    {
        // d($data, $rules);
        $isValid = true;
        global $errors;
        $errors = [];

        foreach ($rules as $dataKey => $ruleString) {
            if (!$this->checkIfExistsValueForRule($data, $dataKey, $errors)) {
                continue;
            }

            foreach (explode('|', $ruleString) as $rule) {
                [$rule, $values] = $this->getRuleAndValues($rule);
                $ruleMethodName  = $this->checkRule($rule);

                if ($error = $this->{$ruleMethodName}($data, $dataKey, ...$values)) {
                    $errors[] = $this->getRuleErrorMessage($rule, [$dataKey, ...$values]);
                }
            }
        }

        // if ($errors) {
        //     d($errors);
        // }

        return $errors;
    }

    private function getRuleAndValues(string $rule): array
    {
        if (strpos($rule, ':') !== false) {
            $ruleValues = explode(':', $rule);
            $rule       = array_shift($ruleValues);
            $values     = $ruleValues;
        } else {
            $values = [];
        }

        return [$rule, $values];
    }


    protected function emailRule(&$data, $key): bool
    {
        return !filter_var($data[$key], FILTER_VALIDATE_EMAIL);
    }

    protected function requiredRule(&$data, $key): bool
    {
        return $data[$key] === '';
    }

    protected function maxRule(&$data, $key, $max): bool
    {
        return !is_numeric($max) || $data[$key] > (int)$max;
    }

    protected function minRule(&$data, $key, $min): bool
    {
        return !is_numeric($min) || $data[$key] < (int)$min;
    }

    protected function betweenRule(&$data, $key, $min, $max): bool
    {
        return !is_numeric($min) || $data[$key] < (int)$min || $data[$key] > (int)$max;
    }

    protected function maxlenRule(&$data, $key, $maxstrlen): bool
    {
        return mb_strlen($data[$key]) > (int)$maxstrlen;
    }

    protected function minlenRule(&$data, $key, $minstrlen): bool
    {
        return mb_strlen($data[$key]) < (int)$minstrlen;
    }

    protected function nullableRule(&$data, $key): bool
    {
        return false;
    }

    protected function numericRule(&$data, $key): bool
    {
        return !is_numeric($data[$key]);
    }

    protected function integerRule(&$data, $key): bool
    {
        return !is_numeric($data[$key]) || !ctype_digit($data[$key]);
    }

    protected function getRuleErrorMessage($type, $values): string
    {
        return isset($this->ruleResponses[$type]) ? sprintf($this->ruleResponses[$type], ...$values) : $this->getMessageForNotExistingRule($type);
    }

    protected function getMessageForNotExistingRule($type)
    {
        return "For rule {$type} has no error message";
    }

    protected function checkRule($rule)
    {
        $ruleMethodName = $rule . 'Rule';

        if (!method_exists($this, $ruleMethodName)) {
            throw new InvalidRuleException("Rule {$rule} is not supported");
        }

        return $ruleMethodName;
    }

    protected function checkIfExistsValueForRule($data, $dataKey, &$errors)
    {
        if (!isset($data[$dataKey])) {
            $errors[$dataKey] = "There is no value for field {{$dataKey}}";
            return false;
        }

        return true;
    }
}
