
class PydasException(Exception):
    """
    Custom exception to throw within pydas
    """

    def __init__(self, value):
        """
        Override the constructor to support a basic message
        """
        self.value = value

    def __str__(self):
        """
        Override the string method
        """
        return repr(self.value)

