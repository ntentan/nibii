<?php

namespace ntentan\nibii;

class Behaviour {

    public function preSaveCallback($data) {
        return $data;
    }

    public function preUpdateCallback($data) {
        return $data;
    }

    public function postSaveCallback($data) {
        return $data;
    }

    public function postUpdateCallback($data) {
        return $data;
    }

}
