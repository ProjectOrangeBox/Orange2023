<?php

declare(strict_types=1);

namespace peels\flashmsg;

use orange\framework\base\Singleton;
use peels\session\SessionInterface;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\EventInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\OutputInterface;
use orange\framework\exceptions\config\ConfigFileNotFound;
use orange\framework\interfaces\InputInterface;

class Flashmsg extends Singleton implements FlashMsgInterface
{
    use ConfigurationTrait;

    protected array $config = [];
    protected array $messages = [];

    protected SessionInterface $session;
    protected InputInterface $input;
    protected OutputInterface $output;

    protected DataInterface $data;
    protected EventInterface $event;

    protected string $sessionMsgKey;
    protected string $defaultType;
    protected array $stickyTypes;
    protected string $httpReferer;

    /**
     * 
     * @param array $config 
     * @param SessionInterface $session 
     * @param InputInterface $input
     * @param OutputInterface $output 
     * @param ?DataInterface $data 
     * @param ?EventInterface $event 
     * @return void 
     * @throws ConfigFileNotFound 
     * @throws InvalidValue 
     */
    protected function __construct(array $config, SessionInterface $session, InputInterface $input, OutputInterface $output, ?DataInterface $data = null, ?EventInterface $event = null)
    {
        $this->config = $this->mergeConfigWith($config);

        $this->session = $session;
        $this->input = $input;
        $this->output = $output;
        $this->data = $data;
        $this->event = $event;

        $this->sessionMsgKey = $this->config['session msg key'];
        $this->defaultType = $this->config['default type'];
        $this->stickyTypes = $this->config['sticky types'];

        // used for redirect - this should be retrived from input service
        $this->httpReferer = $this->input->get();

        /* are there any messages in cold storage? */
        $previousMessages = $this->session->get($this->sessionMsgKey);

        if (is_array($previousMessages)) {
            $this->messages = $previousMessages;
            $this->session->remove($this->sessionMsgKey);
        }

        /* set the view variable for this page */
        $this->refreshVieDataVariable();
    }

    /**
     * add a flash msg
     */
    public function msg(string $msg, ?string $type = null): self
    {
        $type = ($type) ?? $this->defaultType;

        /* is this type sticky? - use names not colors - colors support for legacy code */
        $sticky = in_array($type, $this->stickyTypes);

        if ($this->event) {
            /* trigger a event incase they need to do something */
            $this->event->trigger('flash.msg', $msg, $type, $sticky);
        }

        $this->messages[sha1($type . $msg)] = [
            'type' => $type,
            'msg' => trim($msg),
            'sticky' => $sticky,
        ];

        /* put in view variable incase they want to use it on this page */
        return $this->refreshVieDataVariable();
    }

    /**
     * add multiple flash msgs
     */
    public function msgs(array $array, ?string $type = null): self
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

    public function redirect(?string $redirect = null): void
    {
        // store this in a session variable for redirect
        $this->session->set($this->sessionMsgKey, $this->messages);

        $this->output->redirect($redirect ?? $this->httpReferer);
    }

    /**
     * return all of the messages
     *
     * detailed in array or just array of messages
     */
    public function getMessages(bool $detailed = false): array
    {
        $messages = array_values($this->messages);

        return ($detailed) ? ['messages' => $messages, 'count' => count($this->messages), 'initial_pause' => $this->config['initial pause'], 'pause_for_each' => $this->config['pause for each']] : $messages;
    }

    /* set the view variable stored in data (for view) to something other that default */
    protected function refreshVieDataVariable(): self
    {
        if ($this->data) {
            $this->data[$this->config['view variable']] = $this->getMessages(true);
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
