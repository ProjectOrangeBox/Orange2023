<?php

declare(strict_types=1);

namespace peels\flashmsg;

use peels\session\SessionInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\EventInterface;
use orange\framework\interfaces\OutputInterface;

class Flashmsg implements FlashMsgInterface
{
    use ConfigurationTrait;

    protected array $config = [];

    private ?FlashMsgInterface $instance = null;

    protected array $messages = [];

    protected SessionInterface $session;
    protected OutputInterface $output;

    protected ?DataInterface $data = null;
    protected ?EventInterface $event = null;

    protected string $viewVariable;
    protected string $sessionMsgKey;
    protected string $defaultType;
    protected array $stickyTypes;
    protected string $httpReferer;
    protected int $initialPause;
    protected int $pauseForEach;

    protected function __construct(array $config, SessionInterface $session, OutputInterface $output, ?DataInterface $data = null, ?EventInterface $event = null)
    {
        $this->config = $this->mergeWith($config);

        $this->session = $session;
        $this->output = $output;

        if ($data) {
            $this->data = $data;
        }

        if ($event) {
            $this->event = $event;
        }

        $this->viewVariable = $this->config['view variable'];
        $this->sessionMsgKey = $this->config['session msg key'];
        $this->defaultType = $this->config['default type'];
        $this->stickyTypes = $this->config['sticky types'];
        $this->httpReferer = $this->config['http referer'];
        $this->initialPause = $this->config['initial pause'];
        $this->pauseForEach = $this->config['pause for each'];

        /* are there any messages in cold storage? */
        if (is_array($previousMessages = $this->session->get($this->sessionMsgKey))) {
            $this->messages = $previousMessages;

            $this->session->remove($this->sessionMsgKey);
        }

        /* set the view variable for this page */
        $this->refreshData();
    }

    public static function getInstance(array $config, SessionInterface $session, OutputInterface $output, ?DataInterface $data = null, ?EventInterface $event = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config, $session, $output, $data, $event);
        }

        return self::$instance;
    }

    /**
     * add a flash msg
     */
    public function msg(string $msg, string $type = null): self
    {
        $type = ($type) ?? $this->defaultType;

        /* is this type sticky? - use names not colors - colors support for legacy code */
        $sticky = in_array($type, $this->stickyTypes);

        if ($this->event) {
            /* trigger a event incase they need to do something */
            $this->event->trigger('flash.msg', $msg, $type, $sticky);
        }

        $this->messages[sha1($type . $msg)] = [
            'msg' => trim($msg),
            'type' => $type,
            'sticky' => $sticky,
        ];

        /* put in view variable incase they want to use it on this page */
        return $this->refreshData();
    }

    /**
     * add multiple flash msgs
     */
    public function msgs(array $array, string $type = null): self
    {
        $type = ($type) ?? $this->defaultType;

        foreach ($array as $a => $b) {
            if (is_numeric($a)) {
                $this->msg($b, $type);
            } else {
                $this->msg($a, $b);
            }
        }

        return $this;
    }

    /**
     * if redirect @ we are automatically redirected to the HTTP_REFERER
     */
    public function redirect(string $redirect): void
    {
        // if it starts with @ then use the referer
        if (substr($redirect, 0, 1) == '@') {
            /* where did we come from? */
            $redirect = $this->httpReferer;
        }

        // store this in a session variable for redirect
        $this->session->set($this->sessionMsgKey, $this->messages);

        $this->output->redirect($redirect);
    }

    /**
     * return all of the messages
     *
     * detailed in array or just array of messages
     */
    public function getMessages(bool $detailed = false): array
    {
        $messages = array_values($this->messages);

        return ($detailed) ? ['messages' => $messages, 'count' => count($this->messages), 'initial_pause' => $this->initialPause, 'pause_for_each' => $this->pauseForEach] : $messages;
    }

    /* set the view variable stored in data (for view) to something other that default */
    protected function refreshData(): self
    {
        if ($this->data) {
            $this->data[$this->viewVariable] = $this->getMessages(true);
        }

        return $this;
    }

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'messages' => $this->messages,
        ];
    }
}
