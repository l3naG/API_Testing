<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    protected $response = null;
    protected $username = null;
    protected $password = null;
    protected $client = null;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     * @param $github_username
     * @param $github_password
     */
    public function __construct($github_username, $github_password)
    {
        $this->username = $github_username;
        $this->password = $github_password;
    }

    /**
     * @Given I am an anonymous user
     */
    public function iAmAnAnonymousUser()
    {
        return true;
    }

    /**
     * @When I search for :arg1
     */
    public function iSearchFor($arg1)
    {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.github.com']);
        $this->response = $client->get('/search/repositories?q=' . $arg1);
    }

    /**
     * @Then I expect a :arg1 response code
     * @throws Exception
     */
    public function iExpectAResponseCode($arg1)
    {
        $response_code = $this->response->getStatusCode();
        if ($response_code <> $arg1) {
            throw new Exception("it did not work, expected $arg1 but got a " . $response_code);
        }
    }

    /**
     * @Then I expect at least :arg1 result
     * @throws Exception
     */
    public function iExpectAtLeastResult($arg1)
    {
        $response_code = $this->response->getStatusCode();
        if ($response_code <> 200) {
            throw new Exception("it did not work, expected 200 but got a " . $response_code);
        }
        $data = $this->getBodyAsJson();
        if ($data['total_count'] < $arg1) {
            throw new Exception("we expected at least $arg1 results but found: " . $data['total_count']);
        }
    }

    /**
     * @Given I am an authenticated user
     * @throws Exception
     */
    public function iAmAnAuthenticatedUser()
    {
        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'https://api.github.com',
            'auth' => [$this->username, $this->password]
        ]);
        $this->response = $this->client->get('/');
        $this->iExpectAResponseCode(200);
    }

    /**
     * @When I request a list of my repositories
     * @throws Exception
     */
    public function iRequestAListOfMyRepositories()
    {
        $this->response = $this->client->get('/user/repos');
        $this->iExpectAResponseCode(200);
    }

    /**
     * @Then The result should include a repository :arg1
     * @throws Exception
     */
    public function theResultShouldIncludeARepository($arg1)
    {
        $repositories = $this->getBodyAsJson();
        foreach ($repositories as $repository) {
            if ($repository['name'] == $arg1) {
                return true;
            }
        }
        throw new Exception("Expected to find repository named '$arg1' but did not");
    }

    /**
     * @When I create the :arg1 repository
     * @throws Exception
     */
    public function iCreateTheRepository($arg1)
    {
        $parameters = json_encode(['name' => $arg1]);
        $this->client->post('/user/repos', ['body' => $parameters]);
        $this->iExpectAResponseCode(200);
    }

    /**
     * @Given I have a repository called :arg1
     * @throws Exception
     */
    public function iHaveARepositoryCalled($arg1)
    {
        $this->iRequestAListOfMyRepositories();
        $this->theResultShouldIncludeARepository($arg1);
    }

    /**
     * @When I watch the :arg1 repository
     */
    public function iWatchTheRepository($arg1)
    {
        $watch_url = '/repos/' . $this->username . '/' . $arg1 . '/subscription';
        $parameters = json_encode(['subscribed' => 'true']);
        $this->client->put($watch_url, ['body' => $parameters]);
    }

    /**
     * @Then The :arg1 repository will list me as a watcher
     * @throws Exception
     */
    //TODO: should we refactor this with the other array checking method?
    public function theRepositoryWillListMeAsAWatcher($arg1)
    {
        $watch_url = '/repos/' . $this->username . '/' . $arg1 . '/subscribers';
        $this->response = $this->client->get($watch_url);

        $subscribers = $this->getBodyAsJson();
        foreach ($subscribers as $subscriber) {
            if ($subscriber['login'] == $this->username) {
                return true;
            }
        }
        throw new Exception("Did not find '{$this->username}' as a subscriber");
    }

    /**
     * @Then I delete the repository called :arg1
     * @throws Exception
     */
    public function iDeleteTheRepositoryCalled($arg1)
    {
        $delete = '/repos/' . $this->username . '/' . $arg1 . '/subscription';
        $this->response = $this->client->delete($delete);

        $this->iExpectAResponseCode(204);
    }


    /**
     * @Given I have the following repositories
     */
    public function iHaveTheFollowingRepositories(TableNode $table)
    {
        $this->table = $table->getRows();
        array_shift($this->table);
        foreach ($this->table as $id => $row) {
            $this->table[$id]['name'] = $row[0] . '/' . $row[1];
            $this->response = $this->client->get('/repos/' . $row[0] . '/' . $row[1]);
            $this->iExpectAResponseCode(200);
        }
    }

    /**
     * @When I watch each repository
     */
    public function iWatchEachRepository()
    {
        $parameters = json_encode(['subscribed' => 'true']);
        foreach ($this->table as $row) {
            $watch_url = '/repos/' . $row['name'] . '/subscription';
            $this->client->put($watch_url, ['body' => $parameters]);
        }
    }

    /**
     * @Then My watch list will include those repositories
     * @throws Exception
     */
    public function myWatchListWillIncludeThoseRepositories()
    {
        $watch_url = '/users/' . $this->username . '/subscriptions';
        $this->response = $this->client->get($watch_url);
        $watches = $this->getBodyAsJson();

        foreach ($this->table as $row) {
            $fullname = $row['name'];

            foreach ($watches as $watch) {
                if ($fullname == $watch['full_name']) {
                    break 2;
                }
            }
            throw new Exception("Error! " . $this->username . " is not watching " . $fullname);
        }
    }


    protected function getBodyAsJson()
    {
        return json_decode($this->response->getBody(), true);
    }

    protected function iExpectASuccessfulRequest()
    {
        $response_code = $this->response->getStatusCode();
        if ($response_code <> 200 || $response_code <> 201 || $response_code <> 204) {
            throw new Exception("Did not receive a success response code, instead " . $response_code);
        }
    }

    protected function iExpectAFailedRequest()
    {
        $response_code = $this->response->getStatusCode();
        if ($response_code <> 400 || $response_code <> 401 || $response_code <> 403 || $response_code <> 404) {
            throw new Exception("Did not receive a failure response code, instead " . $response_code);
        }
    }
}
