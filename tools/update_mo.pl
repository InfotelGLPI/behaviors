#!/usr/bin/perl
#!/usr/bin/perl -w 

#
# LICENSE
#
# This file is part of Behaviors plugin for GLPI.
#
# Behaviors is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Behaviors is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with Behaviors. If not, see <http://www.gnu.org/licenses/>.
#
# @author    Infotel, Remi Collet, Nelly Mahu-Lasson
# @copyright Copyright (c) 2018-2026 Behaviors plugin team
# @license   AGPL License 3.0 or (at your option) any later version
# @link      https://github.com/InfotelGLPI/behaviors/
# @link      http://www.glpi-project.org/
# @package   behaviors
# @since     2010
# http://www.gnu.org/licenses/agpl-3.0-standalone.html
# --------------------------------------------------------------------------
#

if (@ARGV!=0){
print "USAGE update_mo.pl\n\n";

exit();
}


opendir(DIRHANDLE,'locales')||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){

            if(!(-l "$dir/$_")){
                     if (index($_,".po",0)==length($_)-3) {
                        $lang=$_;
                        $lang=~s/\.po//;
                        
                        `msgfmt locales/$_ -o locales/$lang.mo`;
                     }
            }

	}
}
closedir DIRHANDLE; 

#  
#  
