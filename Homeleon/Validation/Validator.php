<?php

namespace Homeleon\Validation;

class Validator
{
    private array $ruleResponses;

    public function __construct()
    {
        $fileRules = __DIR__ . '/rules.php';
        $this->ruleResponses = file_exists($fileRules) ? require $fileRules : [];
    }

    /**
     * @throws InvalidRuleException
     */
    public function __invoke($data, $rules): array
    {
        return $this->validate($data, $rules);
    }

    /**
     * @throws InvalidRuleException
     */
    public function validate($data, $rules): array
    {
        $errors = [];

        foreach ($rules as $dataKey => $ruleString) {
            if (!$this->checkIfExistsValueForRule($data, $dataKey, $errors)) {
                continue;
            }

            foreach (explode('|', $ruleString) as $rule) {
                [$rule, $values] = $this->getRuleAndValues($rule);
                $ruleMethodName  = $this->checkRule($rule);

                if ($this->{$ruleMethodName}($data, $dataKey, ...$values)) {
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
        if (str_contains($rule, ':')) {
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

    protected function maxRule(&$data, $key, int $max): bool
    {
        return !is_numeric($max) || $data[$key] > $max;
    }

    protected function minRule(&$data, $key, int $min): bool
    {
        return !is_numeric($min) || $data[$key] < $min;
    }

    protected function betweenRule(&$data, $key, int $min, int $max): bool
    {
        return !is_numeric($min) || $data[$key] < $min || $data[$key] > $max;
    }

    protected function maxlenRule(&$data, $key, int $maxstrlen): bool
    {
        return mb_strlen($data[$key]) > $maxstrlen;
    }

    protected function minlenRule(&$data, $key, int $minstrlen): bool
    {
        return mb_strlen($data[$key]) < $minstrlen;
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

    protected function getMessageForNotExistingRule($type): string
    {
        return "For rule {$type} has no error message";
    }

    /**
     * @throws InvalidRuleException
     */
    protected function checkRule($rule): string
    {
        $ruleMethodName = $rule . 'Rule';

        if (!method_exists($this, $ruleMethodName)) {
            throw new InvalidRuleException("Rule {$rule} is not supported");
        }

        return $ruleMethodName;
    }

    protected function checkIfExistsValueForRule(array $data, string $dataKey, array &$errors): bool
    {
        if (!isset($data[$dataKey])) {
            $errors[$dataKey] = "There is no value for field {{$dataKey}}";
            return false;
        }

        return true;
    }
}
