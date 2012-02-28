<?php

/** Controller for adding and removing comments */
class Comments_CommentController extends Comments_AppController
{
  public $_models = array('Item');
  public $_moduleModels = array('Itemcomment');

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
    $modelLoader = new MIDAS_ModelLoader();
    $itemCommentModel = $modelLoader->loadModel('Itemcomment', $this->moduleName);
    $itemCommentModel->addComment($this->userSession->Dao, $item, $comment);

    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Comment added'));
    }

  /**
   * Used to refresh the list of comments on the page
   * @param itemId The item id whose comments to get
   * @param limit Max number of comments to display at once
   * @param offset Offset count for pagination
   */
  function getAction()
    {
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
    $limit = $this->_getParam('limit');
    $offset = $this->_getParam('offset');

    $this->disableView();
    $this->disableLayout();
    $componentLoader = new MIDAS_ComponentLoader();
    $commentComponent = $componentLoader->loadComponent('Comment', $this->moduleName);
    list($comments, $total) = $commentComponent->getComments($item, $limit, $offset);

    echo JsonComponent::encode(array('status' => 'ok', 'comments' => $comments, 'total' => $total));
    }

  /**
   * Used to delete a comment
   * @param commentId Id of the comment to delete
   */
  function deleteAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in to delete an item');
      }
    $commentId = $this->_getParam('commentId');
    if(!isset($commentId) || !$commentId)
      {
      throw new Zend_Exception('Must set commentId parameter');
      }
    $comment = $this->Comments_Itemcomment->load($commentId);
    if(!$comment)
      {
      throw new Zend_Exception('Not a valid commentId');
      }

    $this->disableView();
    $this->disableLayout();

    if($this->userSession->Dao->isAdmin() || $this->userSession->Dao->getKey() == $comment->getUserId())
      {
      $this->Comments_Itemcomment->delete($comment);
      $retVal = array('status' => 'ok', 'message' => "Comment deleted");
      }
    else
      {
      $retVal = array('status' => 'error', 'message' => "Cannot delete comment (permission denied)");
      }
    echo JsonComponent::encode($retVal);
    }
}//end class
