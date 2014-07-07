<?php

namespace h4kuna\Gettext\Macros;

use ArrayIterator;

/**
 *
 * @author Milan Matějček
 */
class Latte2PhpTokenizer {

    /** @var array */
    private $args = array();

    /** @var ArrayIterator */
    private $tokens;

    public function __construct($string) {
        $this->tokens = new ArrayIterator(array_slice(token_get_all('<?php _(' . $string . ');'), 3, -2));
        foreach ($this->tokens as $token) {
            $val = $this->param();
            if ($val) {
                $this->args[] = $val;
            } else {
                dd($token);
            }
        }
    }

    public function __toString() {
        return implode(',', $this->args);
    }

    /**
     * 
     * @return array
     */
    public function getArgs() {
        return $this->args;
    }

    private function param() {
        $param = NULL;
        $inner = 0;
        do {
            $current = $this->tokens->current();
            if (is_array($current)) {
                if ($current[0] == T_WHITESPACE) {
                    continue;
                }
                $param .= $current[1];
            } else {
                if ($current == '(') {
                    ++$inner;
                } elseif ($inner > 0 && $current == ')') {
                    --$inner;
                } elseif ($current == ',' && !$inner) {
                    break;
                }
                $param .= $current;
            }
        } while (($this->tokens->next() || 1) && $this->tokens->valid());
        return $param;
    }

}
