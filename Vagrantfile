# -*- mode: ruby -*-
# vi: set ft=ruby :
#=============================================================================
# Midas Server
# Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
# All rights reserved.
# For more information visit http://www.kitware.com/.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0.txt
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#=============================================================================

Vagrant.configure('2') do |config|
    if Vagrant.has_plugin?('vagrant-cachier')
        config.cache.auto_detect = false
        config.cache.enable :apt
        config.cache.enable :apt_lists
        config.cache.enable :composer
        config.cache.scope = :box
    end
    config.vm.box = 'ubuntu/trusty64'
    config.vm.network 'forwarded_port', guest: 80, host: 8080, auto_correct: true
    config.vm.provision 'ansible' do |ansible|
        ansible.playbook = 'provisioning/ansible/site.yml'
    end
end
