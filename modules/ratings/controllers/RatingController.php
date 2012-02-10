<?php

/** Controller for setting and getting ratings */
class Ratings_RatingController extends Ratings_AppController
{
  public $_models = array('Item');

  /**
   * Set a rating on an item
   * @param itemId The item id to set the rating on
   * @param rating The rating (0-5) to set for the currently logged user. 0 means remove user's rating.
   */
  function rateitemAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in to rate an item');
      }

    $itemId = $this->_getParam('itemId');
    if(!isset($itemId) || !$itemId)
      {
      throw new Zend_Exception('Must set itemId parameter');
      }
    $item = $this->Item->load($itemId);
    if(!$item)
      {
      throw new Zend_Exception('Not a valid itemId');
      }
    $rating = (int)$this->_getParam('rating');
    if($rating < 0 || $rating > 5)
      {
      throw new Zend_Exception('Rating must be 0-5');
      }

    $this->disableView();
    $this->disableLayout();
    $modelLoader = new MIDAS_ModelLoader();
    $itemRatingModel = $modelLoader->loadModel('Itemrating', $this->moduleName);
    $itemRatingModel->setRating($this->userSession->Dao, $item, $rating);

    $message = $rating == 0 ? 'Rating removed' : 'Rating saved';
    echo JsonComponent::encode(array('status' => true, 'message' => $message));
    }

}//end class