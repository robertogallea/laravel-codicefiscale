<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

interface Check {
    public function check($code);
}