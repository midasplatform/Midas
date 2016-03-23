<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** UtilityComponentTest. */
class Core_UtilityComponentTest extends ControllerTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->_components = array('Utility');
        parent::setUp();
    }

    /**
     * Make sure that we are safely filtering html tags.
     */
    public function testFilterHtmlTags()
    {
        // Assert that plain text with no tags is unchanged
        $text = 'test plain text';
        $val = UtilityComponent::filterHtmlTags($text);
        $this->assertEquals($text, $val);

        // Assert that we allow certain tags
        $text = '<b>bold</b><br><br /><i>italic</i><p>paragraph</p><a href="http://site.com">anchor</a><div>Div</div>';
        $val = UtilityComponent::filterHtmlTags($text);
        $this->assertEquals($text, $val);

        // Assert that we strip disallowed attributes such as id
        $text = '<a id="idLink">anchor</a>';
        $val = UtilityComponent::filterHtmlTags($text);
        $this->assertEquals($val, '<a>anchor</a>');

        // Assert that we strip disallowed tags such as script
        $text = '<script type="text/javascript">malicious javascript</script>';
        $val = UtilityComponent::filterHtmlTags($text);
        $this->assertEquals($val, 'malicious javascript');
    }

    /**
     * Test longestCommonSuffix function.
     */
    public function testLongestCommonSuffix()
    {
        $s1 = '';
        $s2 = '';
        $commonSuffix = '';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = '';
        $s2 = 'Greedy Error';
        $commonSuffix = '';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'Greedy Error';
        $s2 = '';
        $commonSuffix = '';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'MST VOODOO 50th percentile Greedy Error';
        $s2 = 'MST VOODOO 50th percentile Greedy Errory';
        $commonSuffix = '';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'MST VOODOO 50th percentile Greedy Errory';
        $s2 = 'MST VOODOO 50th percentile Greedy Error';
        $commonSuffix = '';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'MST VOODOO 50th percentile Greedy Error';
        $s2 = 'MST HOODOO 50th percentile Greedy Error';
        $commonSuffix = 'OODOO 50th percentile Greedy Error';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'MST HOODOO 50th percentile Greedy Error';
        $s2 = 'MST VOODOO 50th percentile Greedy Error';
        $commonSuffix = 'OODOO 50th percentile Greedy Error';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'MST HOODOO 50th percentile Greedy Error';
        $s2 = 'Greedy Error';
        $commonSuffix = 'Greedy Error';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'Greedy Error';
        $s2 = 'MST HOODOO 50th percentile Greedy Error';
        $commonSuffix = 'Greedy Error';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);

        $s1 = 'MST VOODOO 50th percentile Greedy Error';
        $s2 = 'MST VOODOO 50th percentile Greedy Error';
        $commonSuffix = 'MST VOODOO 50th percentile Greedy Error';
        $this->assertEquals(UtilityComponent::longestCommonSuffix($s1, $s2), $commonSuffix);
    }
}
