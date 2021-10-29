<?php

namespace Core\Http;

use Core\Validation\Validator;
use Core\Session\Session;

class Controller
{
    public function __construct(
        private Validator $validator,
        private Session $session,
        private Response $response,
    ) {}

    public function validate($data, array $rules)
    {
        if (!empty($errors = $this->validator->validate($data, $rules))) {
            $this->session->set('_errors', $errors);
            exit($this->response->redirect()->back()->getContent());
        }

        return true;
    }
}
