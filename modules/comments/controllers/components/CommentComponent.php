<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

/** Helper component for the comments module */
class Comments_CommentComponent extends AppComponent
{
  public function getComments($item, $limit, $offset)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $itemCommentModel = $modelLoader->loadModel('Itemcomment', 'comments');
    $componentLoader = new MIDAS_ComponentLoader();
    $dateComponent = $componentLoader->loadComponent('Date');
    $comments = $itemCommentModel->getComments($item, $limit, $offset);
    $commentsList = array();
    foreach($comments as $comment)
      {
      $commentArray = $comment->toArray();
      $commentArray['user'] = $comment->getUser()->toArray();
      $commentArray['comment'] = htmlentities($commentArray['comment']);
      $commentArray['ago'] = $dateComponent->ago($commentArray['date']);
      $commentsList[] = $commentArray;
      }
    return $commentsList;
    }
} // end class
