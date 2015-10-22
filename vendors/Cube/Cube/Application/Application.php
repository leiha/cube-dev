<?php
/**
 * Class Application
 * @author Leiha Sellier <leiha.sellier@gmail.com>
 * @link   https://github.com/leiha
 * -
 */

namespace Cube\Application;

use Cube\Cube;
use Cube\Dna\Dna;
use Cube\Error\ErrorException;
use Cube\FileSystem\AutoLoader\AutoLoader;
use Cube\FileSystem\FileSystem;
use Cube\Generator\ClassGenerator;
use Cube\Http\Router\Router;
use Cube\Http\Router\RouterBehavior;
use Cube\Poo\Exception\Exception;
use Cube\Poo\Mapper\Mapper;

class Application
	extends Cube
{
    /**
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * @var Dna
     */
    protected $dna;

    /**
     * @var RouterBehavior
     */
    protected $router;

    /**
     * @param \Closure|null $cbOnStart
     * @return static
     */
    public static function init(\Closure $cbOnStart = null)
    {
        Mapper::init();
        return self::single($cbOnStart);
    }

	/**
	 * @param \Closure $cbOnStart
	 */
	public function __construct(\Closure $cbOnStart = null)
	{
        $this->initException();
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->initError();

        $fileSystem = $this->fileSystem();
        $mapper     = self::mapper()->mapAll($fileSystem->crawler()->findClasses());

        parent::__construct($cbOnStart);

        $router     = $this->router();

        $this->dna()
            ->injectInstance('cube.mapper'    , $mapper)
            ->injectInstance('cube.fileSystem', $fileSystem)
            ->injectInstance('cube.router'    , $router)
        ;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->router()->run();
    }

    /**
     * @return ApplicationFacade
     */
    public function getConfigurator()
    {
        return $this->configurator;
    }

    /**
     * @return RouterBehavior
     */
    public function router()
    {
        return Router::single();
    }

    /**
     * @return ApplicationFacade
     */
    public static function getConfiguratorInstance()
    {
        return ApplicationFacade::instance();
    }

    /**
     * @return Dna
     */
    public function dna()
    {
        return Dna::single();
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        if(!$this->fileSystem) {
            $this->fileSystem = FileSystem::single(AutoLoader::getListOfIncludeFiles(false));
        }
        return $this->fileSystem;
    }

    /**
     * @return AutoLoader
     */
    public function autoLoader()
    {
        return AutoLoader::single();
    }

    /**
     * @throws ErrorException
     */
    private function initError()
    {
        /**
         * @param int $code
         * @param string $msg
         * @param string $file
         * @param int $line
         * @param array $context
         * @throws ErrorException
         */
        $handler = function($code, $msg, $file, $line, $context)
        {
            throw (new ErrorException($msg))
                    ->setFile($file, $line)
                    ;
        };
        set_error_handler($handler);
        return $this;
    }

	/**
	 * @return $this
	 */
	private function initException()
	{
		$handler = function(\Exception $exception)
        {
			//$eClass = $this->configurator->getMapping(Cube::MAPPING_EXCEPTION);
			if(!($exception instanceof Exception)) {
				$e = Exception::instance($exception->getMessage());
				$e->setFile($exception->getFile(), $exception->getLine());
				$exception = $e;
			}
			print($exception->render());
			exit;
		};
		set_exception_handler($handler);
		return $this;
	}
}