<?php
/*
Plugin Name: symfony-components
Description: Symfony components
Author: connor
Author URI: http://blog.connor.hu
Version: 1.0
Stable Tag: 1.0
*/

require(__DIR__ .'/vendor/autoload.php');

use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;

use Symfony\Component\Translation\Translator;
// use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

class SymfonyForm
{
    private static $storage;
    private static $factoryBuilder;
    private static $csrfManager;

    public static function init()
    {
        if (self::$storage) return;
        
        $csrfGenerator = new UriSafeTokenGenerator();
        self::$storage = new NativeSessionTokenStorage();
        self::$csrfManager = new CsrfTokenManager($csrfGenerator, self::$storage);
        
        self::$storage->hasToken(''); // workround. NativeSessionTokenStorage session start
        
        self::$factoryBuilder = Forms::createFormFactoryBuilder()->addExtension(new CsrfExtension(self::$csrfManager));
    }
    
    public static function getCSRFManager()
    {
        return self::$csrfManager;
    }
    
    public static function getFactory()
    {
        return self::$factoryBuilder->getFormFactory();
    }
}

class TranslatorComponent
{
    private static $translator;

    public static function init()
    {
        self::$translator = new Translator('en');
        // self::$translator->addLoader('xlf', new XliffFileLoader());
        // self::$translator->addResource('xlf',__DIR__.'/path/to/translations/messages.en.xlf','en');
        
    }
    
    public static function getTranslator()
    {
        return self::$translator;
    }
}

class TwigComponent
{
    private static $twig;
    
    public static function init()
    {
        $defaultFormTheme = 'bootstrap_3_horizontal_layout.html.twig';

        $vendorDir = realpath(__DIR__ .'/vendor');
        $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());

        self::$twig = new Twig_Environment(new Twig_Loader_Filesystem(array(
            $vendorTwigBridgeDir .'/Resources/views/Form',
        )));

        $formEngine = new TwigRendererEngine(array($defaultFormTheme));
        $formEngine->setEnvironment(self::$twig);
        
        self::$twig->addExtension(new FormExtension(new TwigRenderer($formEngine, SymfonyForm::getCSRFManager())));
        self::$twig->addExtension(new TranslationExtension(TranslatorComponent::getTranslator()));
    }

    public static function env()
    {
        return self::$twig;
    }
    
    public static function addViewDirectory($directory)
    {
        self::$twig->getLoader()->addPath($directory);
    }
    
    public static function addFunction($name, $callback, $options = [])
    {
        $function = new Twig_SimpleFunction($name, $callback, $options);
        self::$twig->addFunction($function);
    }
}

add_action('plugins_loaded', function () {
    SymfonyForm::init();
    TranslatorComponent::init();
    TwigComponent::init();
}, 0);
