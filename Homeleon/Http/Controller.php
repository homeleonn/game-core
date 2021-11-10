<?php

namespace Homeleon\Http;

use Homeleon\Validation\Validator;
use Homeleon\Session\Session;

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
            $this->session->set('_old', \Request::all());
            $this->response->redirect()->back()->getContent();
        }

        return true;
    }
}
