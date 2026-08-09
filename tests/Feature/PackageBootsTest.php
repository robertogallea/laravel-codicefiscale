<?php

test('the Testbench application boots', function () {
    expect($this->app)->toBeInstanceOf(Illuminate\Foundation\Application::class);
});
