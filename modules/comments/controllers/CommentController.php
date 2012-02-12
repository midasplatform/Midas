<?php

/** Controller for adding and removing comments */
class Comments_CommentController extends Comments_AppController
{
  public $_models = array('Item');

  /**
   * Add a comment on an item
   * @param itemId The item id to add the comment to
   * @param comment The text of the comment
   */
  function addAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in to comment on an item');
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
    $comment = $this->_getParam('comment');

    $this->disableView();
    $this->disableLayout();
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Comment added'));
    }

}//end class
