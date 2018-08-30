<?php
/*
 *
 */
namespace FishPig\WordPress\Controller;

/* Parent Class */
use Magento\Framework\App\Action\Action as ParentAction;

/* Constructor Args */
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use FishPig\WordPress\Model\Url as Url;

abstract class Action extends ParentAction
{
	/*
	 * @var 
	 */
	protected $registry;

	/*
	 * @var 
	 */	
	protected $entity;

	/*
	 * @var 
	 */	
	protected $resultPage;

	/*
	 * @var
	 */
	protected $url;
	
	/*
	 * @var 
	 */
	abstract protected function _getEntity();

  /*
   * Constructor
   *
   * @param Context $context
   * @param PageFactory $resultPageFactory
   */
  public function __construct(
		 Context $context, 
		Registry $registry,
		     Url $url
  )
  {
		$this->registry = $registry;
		$this->url      = $url;
        	
    parent::__construct($context);
  }	

  /**
   * Load the page defined in view/frontend/layout/samplenewpage_index_index.xml
   *
   * @return \Magento\Framework\View\Result\Page
   */
  public function execute()
  {
    try {
      $this->_beforeExecute();
  		
  		if ($forward = $this->_getForwardForPreview()) {
  			return $forward;
  		}
  		
  		if ($forward = $this->_getForward()) {
  			return $forward;
  		}
  
  		$this->checkForAmp();
		
	    $this->_initLayout();

	    $this->_afterExecute();

	    return $this->getPage();
  	}
  	catch (\Exception $e) {
  		return $this->_getNoRouteForward();
  	}
  }

	/**
	 *
	**/
	protected function _getForward()
	{
		return false;
	}

	/*
	 *
	 */
	protected function _beforeExecute()
	{
    if (($entity = $this->_getEntity()) === false) {
      throw new \Magento\Framework\Exception\NotFoundException(__('Entity not found!'));
    }
	    
    if ($entity !== null) {
		  $this->registry->register($entity::ENTITY, $entity);
		}

		return $this;	
	}
	
  /*
	 *
	 */
  protected function _initLayout()
  {
	  // Remove the default action layout handle
	  // This allows controller to add handles in chosen order
		$this->getPage()->getLayout()->getUpdate()->removeHandle($this->getPage()->getDefaultLayoutHandle());
		
    if ($handles = $this->getLayoutHandles()) {
	    foreach($handles as $handle) {
				$this->getPage()->addHandle($handle);
			}
		}

    $this->getPage()->getConfig()->addBodyClass('is-blog');

		if ($breadcrumbsBlock = $this->_view->getLayout()->getBlock('breadcrumbs')) {	    
	    if ($crumbs = $this->_getBreadcrumbs()) {
		    foreach($crumbs as $key => $crumb) {
			    $breadcrumbsBlock->addCrumb($key, $crumb);
		    }
	    }
		}

    return $this;
  }
    
  /*
	 * Get an array of extra layout handles to apply
	 *
	 * @return array
	 */
  public function getLayoutHandles()
  {
	  return ['wordpress_default'];
  }

 /*
  * Get the breadcrumbs
  *
  * @return array
  */
  protected function _getBreadcrumbs()
  {
    $crumbs = [
	    'home' => [
			'label' => __('Home'),
			'title' => __('Go to Home Page'),
			'link' => $this->url->getMagentoUrl()
		]];
	
		if (!$this->app->isRoot()) {
			$crumbs['blog'] = [
				'label' => __('Blog'),
				'link' => $this->url->getHomeUrl()
			];
		}
	
		return $crumbs;
	}
  
  /*
	 *
	 */
	protected function _afterExecute()
	{
		return $this;
	}
    
	/*
	 * @var 
	 */
	public function getPage()
	{
		if ($this->resultPage === null) {
			$this->resultPage = $this->resultFactory->create(
				\Magento\Framework\Controller\ResultFactory::TYPE_PAGE
			);
		}
		
		return $this->resultPage;
	}

	/*
	 * @var 
	 */
	public function getEntityObject()
  {
    if ($this->entity !== null) {
	    return $this->entity;
    }

    return $this->entity = $this->_getEntity();
  }
    
  /*
	 * @return bool
	 */
  protected function _canPreview()
  {
    return false;
  }
    
	/**
	 *
	**/
    protected function _getForwardForPreview()
    {
	    if (!$this->_canPreview()) {
		    return false;
	    }

		if ($this->getRequest()->getParam('preview') !== 'true') {
			return false;
		}
		
		if ($entity = $this->_getEntity()) {
			$this->registry->unregister($entity::ENTITY);
		}

		foreach(['p', 'page_id', 'preview_id'] as $previewIdKey) {
			if (0 !== (int)$this->getRequest()->getParam($previewIdKey))	{
				return $this->resultFactory
					->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD)
					->setModule('wordpress')
					->setController('post')
					->setParams(['preview_id' => (int)$this->getRequest()->getParam($previewIdKey)])
					->forward('preview');
			}
		}

		return false;
    }
    
    /**
	  *
	  * @return bool
	  *
	 **/
    public function checkForAmp()
    {
	    return false;
    }
  
  /*
   *
   * @return \Magento\Framework\Controller\ResultForwardFactory
   *
   */
  protected function _getNoRouteForward()
  {
		return $this->resultFactory
			->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD)
			->setModule('cms')
			->setController('noroute')
			->forward('index');
  }
}
