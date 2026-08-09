<?php

test('the Testbench application boots with the package installed', function () {
    expect($this->app)->toBeInstanceOf(Illuminate\Foundation\Application::class);
});
