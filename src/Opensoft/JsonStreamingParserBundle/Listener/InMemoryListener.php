<?php
namespace Opensoft\JsonStreamingParserBundle\Listener;

/**
 * This basic implementation of a listener simply constructs an in-memory
 * representation of the JSON document, which is a little silly since the whole
 * point of a streaming parser is to avoid doing just that. However, it can
 * serve as a starting point for more complex listeners, and illustrates some
 * useful concepts for working with a streaming-style parser.
 */
class InMemoryListener implements ListenerInterface
{
    private $result;
    private $stack;
    private $keys;

    public function getJson()
    {
        return $this->result;
    }

    public function startDocument()
    {
        $this->stack = array();
        $this->keys = array();
    }

    public function startObject()
    {
        $this->startComplexValue('object');
    }

    public function endObject()
    {
        $this->endComplexValue();
    }

    public function startArray()
    {
        $this->startComplexValue('array');
    }

    public function endArray()
    {
        $this->endComplexValue();
    }

    public function key($key)
    {
        $this->keys[] = $key;
    }

    public function value($value)
    {
        $this->insertValue($value);
    }

    private function startComplexValue($type)
    {
        // We keep a stack of complex values (i.e. arrays and objects) as we build them,
        // tagged with the type that they are so we know how to add new values.
        $current_item = array('type' => $type, 'value' => array());
        $this->stack[] = $current_item;
    }

    private function endComplexValue()
    {
        $obj = array_pop($this->stack);

        // If the value stack is now empty, we're done parsing the document, so we can
        // move the result into place so that get_json() can return it. Otherwise, we
        // associate the value
        if (empty($this->stack)) {
            $this->result = $obj['value'];
        } else {
            $this->insertValue($obj['value']);
        }
    }

    // Inserts the given value into the top value on the stack in the appropriate way,
    // based on whether that value is an array or an object.
    private function insertValue($value)
    {
        // Grab the top item from the stack that we're currently parsing.
        $current_item = array_pop($this->stack);

        // Examine the current item, and then:
        //   - if it's an object, associate the newly-parsed value with the most recent key
        //   - if it's an array, push the newly-parsed value to the array
        if ($current_item['type'] === 'object') {
            $current_item['value'][array_pop($this->keys)] = $value;
        } else {
            $current_item['value'][] = $value;
        }

        // Replace the current item on the stack.
        $this->stack[] = $current_item;
    }

    public function endDocument() {}
}
