<?php

/*
 * This file is part of Hiject.
 *
 * Copyright (C) 2016 Hiject Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../Base.php';

use Hiject\Bus\Event\GenericEvent;
use Hiject\Model\TaskCreationModel;
use Hiject\Model\CommentModel;
use Hiject\Model\ProjectModel;
use Hiject\Model\ProjectUserRoleModel;
use Hiject\Model\UserModel;
use Hiject\Action\CommentCreation;
use Hiject\Core\Security\Role;

class CommentCreationTest extends Base
{
    public function testSuccess()
    {
        $userModel = new UserModel($this->container);
        $projectModel = new ProjectModel($this->container);
        $projectUserRoleModel = new ProjectUserRoleModel($this->container);
        $commentModel = new CommentModel($this->container);
        $taskCreationModel = new TaskCreationModel($this->container);

        $this->assertEquals(1, $projectModel->create(['name' => 'test1']));
        $this->assertEquals(1, $taskCreationModel->create(['project_id' => 1, 'title' => 'test']));
        $this->assertEquals(2, $userModel->create(['username' => 'user1']));
        $this->assertTrue($projectUserRoleModel->addUser(1, 2, Role::PROJECT_MEMBER));

        $event = new GenericEvent(['project_id' => 1, 'task_id' => 1, 'comment' => 'test123', 'reference' => 'ref123', 'user_id' => 2]);

        $action = new CommentCreation($this->container);
        $action->setProjectId(1);
        $action->addEvent('test.event', 'Test Event');

        $this->assertTrue($action->execute($event, 'test.event'));

        $comment = $commentModel->getById(1);
        $this->assertNotEmpty($comment);
        $this->assertEquals(1, $comment['task_id']);
        $this->assertEquals('test123', $comment['comment']);
        $this->assertEquals('ref123', $comment['reference']);
        $this->assertEquals(2, $comment['user_id']);
    }

    public function testWithUserNotAssignable()
    {
        $userModel = new UserModel($this->container);
        $projectModel = new ProjectModel($this->container);
        $commentModel = new CommentModel($this->container);
        $taskCreationModel = new TaskCreationModel($this->container);

        $this->assertEquals(1, $projectModel->create(['name' => 'test1']));
        $this->assertEquals(1, $taskCreationModel->create(['project_id' => 1, 'title' => 'test']));
        $this->assertEquals(2, $userModel->create(['username' => 'user1']));

        $event = new GenericEvent(['project_id' => 1, 'task_id' => 1, 'comment' => 'test123', 'user_id' => 2]);

        $action = new CommentCreation($this->container);
        $action->setProjectId(1);
        $action->addEvent('test.event', 'Test Event');

        $this->assertTrue($action->execute($event, 'test.event'));

        $comment = $commentModel->getById(1);
        $this->assertNotEmpty($comment);
        $this->assertEquals(1, $comment['task_id']);
        $this->assertEquals('test123', $comment['comment']);
        $this->assertEquals('', $comment['reference']);
        $this->assertEquals(0, $comment['user_id']);
    }

    public function testWithNoComment()
    {
        $userModel = new UserModel($this->container);
        $projectModel = new ProjectModel($this->container);
        $taskCreationModel = new TaskCreationModel($this->container);

        $this->assertEquals(1, $projectModel->create(['name' => 'test1']));
        $this->assertEquals(1, $taskCreationModel->create(['project_id' => 1, 'title' => 'test']));
        $this->assertEquals(2, $userModel->create(['username' => 'user1']));

        $event = new GenericEvent(['project_id' => 1, 'task_id' => 1]);

        $action = new CommentCreation($this->container);
        $action->setProjectId(1);
        $action->addEvent('test.event', 'Test Event');

        $this->assertFalse($action->execute($event, 'test.event'));
    }
}
